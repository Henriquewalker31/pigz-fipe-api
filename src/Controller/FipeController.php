<?php

namespace App\Controller;

use App\Dto\FipeModelInput;
use App\Dto\FipePriceInput;
use App\Entity\FipeModel;
use App\Entity\FipePrice;
use App\Repository\FipeModelRepository;
use App\Repository\FipePriceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/fipe/models', requirements: ['id' => '\d+', 'priceId' => '\d+'])]
final class FipeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FipeModelRepository $models,
        private readonly FipePriceRepository $prices,
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $q = $request->query;
        $page = max(1, $q->getInt('page', 1));
        $limit = min(100, max(1, $q->getInt('limit', 20)));

        $paginator = $this->models->search([
            'brand' => $q->getString('brand'),
            'model' => $q->getString('model'),
            'fipe_code' => $q->getString('fipe_code'),
            'model_year' => $q->getInt('model_year'),
            'vehicle_type' => $q->getString('vehicle_type'),
        ], $page, $limit);
        $total = count($paginator);

        return $this->json([
            'data' => array_map(fn (FipeModel $m) => $m->toArray(), iterator_to_array($paginator)),
            'meta' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int) ceil($total / $limit)],
        ]);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(#[MapRequestPayload] FipeModelInput $input): JsonResponse
    {
        $this->assertUniqueModel($input);
        $model = new FipeModel($input);
        $this->em->persist($model);
        $this->em->flush();

        return $this->json($model->toArray(), Response::HTTP_CREATED, ['Location' => '/api/fipe/models/' . $model->getId()]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] FipeModel $model): JsonResponse
    {
        $history = $model->getPrices();

        return $this->json($model->toArray() + [
            'latest_price' => isset($history[0]) ? $history[0]->toArray() : null,
            'prices_count' => count($history),
        ]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(#[MapEntity(id: 'id')] FipeModel $model, #[MapRequestPayload] FipeModelInput $input): JsonResponse
    {
        $this->assertUniqueModel($input, $model);
        $model->apply($input);
        $this->em->flush();

        return $this->json($model->toArray());
    }

    /** Veículos vinculados ficam com fipe_model = null (FK ON DELETE SET NULL). */
    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(#[MapEntity(id: 'id')] FipeModel $model): Response
    {
        $this->em->remove($model);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /** Histórico de preços (mais recente primeiro). */
    #[Route('/{id}/prices', methods: ['GET'])]
    public function prices(#[MapEntity(id: 'id')] FipeModel $model): JsonResponse
    {
        return $this->json(array_map(fn (FipePrice $p) => $p->toArray(), $model->getPrices()));
    }

    #[Route('/{id}/prices', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addPrice(#[MapEntity(id: 'id')] FipeModel $model, #[MapRequestPayload] FipePriceInput $input): JsonResponse
    {
        $this->assertUniqueMonth($model, $input->month());
        $price = new FipePrice($model, $input->month(), $input->price);
        $this->em->persist($price);
        $this->em->flush();

        return $this->json($price->toArray(), Response::HTTP_CREATED, [
            'Location' => sprintf('/api/fipe/models/%d/prices/%d', $model->getId(), $price->getId()),
        ]);
    }

    #[Route('/{id}/prices/{priceId}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updatePrice(
        #[MapEntity(id: 'id')] FipeModel $model,
        #[MapEntity(id: 'priceId')] FipePrice $price,
        #[MapRequestPayload] FipePriceInput $input,
    ): JsonResponse {
        $this->assertBelongs($price, $model);
        $this->assertUniqueMonth($model, $input->month(), $price);
        $price->update($input->month(), $input->price);
        $this->em->flush();

        return $this->json($price->toArray());
    }

    #[Route('/{id}/prices/{priceId}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deletePrice(#[MapEntity(id: 'id')] FipeModel $model, #[MapEntity(id: 'priceId')] FipePrice $price): Response
    {
        $this->assertBelongs($price, $model);
        $this->em->remove($price);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function assertUniqueModel(FipeModelInput $in, ?FipeModel $current = null): void
    {
        $existing = $this->models->findOneBy(['fipeCode' => $in->fipeCode, 'modelYear' => $in->modelYear, 'fuel' => $in->fuel]);
        if ($existing && $existing->getId() !== $current?->getId()) {
            throw new ConflictHttpException('Já existe um modelo FIPE com esse código, ano e combustível.');
        }
    }

    private function assertUniqueMonth(FipeModel $model, \DateTimeImmutable $month, ?FipePrice $current = null): void
    {
        $existing = $this->prices->findOneBy(['fipeModel' => $model, 'referenceMonth' => $month]);
        if ($existing && $existing->getId() !== $current?->getId()) {
            throw new ConflictHttpException('Já existe preço cadastrado para esse mês de referência.');
        }
    }

    private function assertBelongs(FipePrice $price, FipeModel $model): void
    {
        if ($price->getFipeModel()->getId() !== $model->getId()) {
            throw $this->createNotFoundException('Preço não encontrado neste modelo FIPE.');
        }
    }
}