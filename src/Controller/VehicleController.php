<?php

namespace App\Controller;

use App\Dto\VehicleInput;
use App\Entity\FipeModel;
use App\Entity\FipePrice;
use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
use App\Repository\FipeModelRepository;
use App\Repository\FipePriceRepository;
use App\Repository\VehicleRepository;
use App\Service\FipeComparator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/vehicles', requirements: ['id' => '\d+'])]
final class VehicleController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly VehicleRepository $vehicles,
        private readonly FipeModelRepository $fipeModels,
        private readonly FipePriceRepository $fipePrices,
        private readonly FipeComparator $comparator,
    ) {}

    /** Listagem com filtros, paginação e o campo fipe_comparison em cada item. */
    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $q = $request->query;
        $status = $q->getString('status', VehicleStatus::AVAILABLE->value);

        if ($status === 'all') {
            $status = null;
        } elseif (!in_array($status, VehicleStatus::values(), true)) {
            throw new UnprocessableEntityHttpException('Status inválido. Use: available, reserved, sold ou all.');
        }
        if ($status !== VehicleStatus::AVAILABLE->value && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Apenas administradores podem listar veículos não disponíveis.');
        }

        $num = fn (string $k) => is_numeric($q->get($k)) ? (float) $q->get($k) : null;
        $page = max(1, $q->getInt('page', 1));
        $limit = min(100, max(1, $q->getInt('limit', 20)));

        $paginator = $this->vehicles->search([
            'status' => $status,
            'brand' => $q->getString('brand'),
            'model' => $q->getString('model'),
            'fuel' => $q->getString('fuel'),
            'year_min' => $q->getInt('year_min'),
            'year_max' => $q->getInt('year_max'),
            'price_min' => $num('price_min'),
            'price_max' => $num('price_max'),
            'sort' => $q->getString('sort', 'newest'),
        ], $page, $limit);

        $items = iterator_to_array($paginator);
        $latest = $this->latestPricesFor($items);
        $total = count($paginator);

        return $this->json([
            'data' => array_map(fn (Vehicle $v) => $this->present($v, $latest), $items),
            'meta' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int) ceil($total / $limit)],
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Vehicle $vehicle): JsonResponse
    {
        return $this->json($this->present($vehicle, $this->latestPricesFor([$vehicle])));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(#[MapRequestPayload] VehicleInput $input): JsonResponse
    {
        $vehicle = new Vehicle($input, $this->resolveFipeModel($input));
        $this->em->persist($vehicle);
        $this->em->flush();

        return $this->json(
            $this->present($vehicle, $this->latestPricesFor([$vehicle])),
            Response::HTTP_CREATED,
            ['Location' => '/api/vehicles/' . $vehicle->getId()],
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Vehicle $vehicle, #[MapRequestPayload] VehicleInput $input): JsonResponse
    {
        $vehicle->apply($input, $this->resolveFipeModel($input));
        $this->em->flush();

        return $this->json($this->present($vehicle, $this->latestPricesFor([$vehicle])));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Vehicle $vehicle): Response
    {
        $this->em->remove($vehicle);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /** Comparação detalhada: preço atual x FIPE e o veículo contra cada mês do histórico. */
    #[Route('/{id}/fipe-comparison', methods: ['GET'])]
    public function comparison(Vehicle $vehicle): JsonResponse
    {
        $fipe = $vehicle->getFipeModel();
        if (!$fipe) {
            throw $this->createNotFoundException('Este veículo não possui modelo FIPE vinculado.');
        }

        $history = $fipe->getPrices();

        return $this->json([
            'vehicle' => $vehicle->toArray(),
            'fipe_model' => $fipe->toArray(),
            'comparison' => $this->comparator->compare($vehicle, $history[0] ?? null),
            'history_summary' => $this->comparator->summarizeHistory($history),
            'history' => array_map(fn (FipePrice $p) => $p->toArray() + [
                'vehicle_vs_fipe_percent' => $this->comparator->compare($vehicle, $p)['difference_percent'],
            ], $history),
        ]);
    }

    private function present(Vehicle $v, array $latestPrices): array
    {
        $fipeId = $v->getFipeModel()?->getId();

        return $v->toArray() + [
            'fipe_comparison' => $this->comparator->compare($v, $fipeId ? ($latestPrices[$fipeId] ?? null) : null),
        ];
    }

    /** @param Vehicle[] $vehicles */
    private function latestPricesFor(array $vehicles): array
    {
        $ids = array_filter(array_map(fn (Vehicle $v) => $v->getFipeModel()?->getId(), $vehicles));

        return $this->fipePrices->findLatestForModels(array_values(array_unique($ids)));
    }

    private function resolveFipeModel(VehicleInput $input): ?FipeModel
    {
        if ($input->fipeModelId === null) {
            return null;
        }

        $fipe = $this->fipeModels->find($input->fipeModelId);
        if (!$fipe) {
            throw new UnprocessableEntityHttpException('O modelo FIPE informado não existe.');
        }
        if ($fipe->getModelYear() !== FipeModel::ZERO_KM_YEAR && $fipe->getModelYear() !== $input->modelYear) {
            throw new UnprocessableEntityHttpException('O ano modelo do veículo não corresponde ao do modelo FIPE.');
        }

        return $fipe;
    }
}