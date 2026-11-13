<?php

namespace App\Command;

use App\Dto\FipeModelInput;
use App\Dto\VehicleInput;
use App\Entity\FipeModel;
use App\Entity\FipePrice;
use App\Entity\Vehicle;
use App\Repository\FipeModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-demo', description: 'Popula o banco com FIPE e veículos fictícios (idempotente).')]
final class SeedDemoCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FipeModelRepository $models,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->models->count([]) > 0) {
            $output->writeln('Dados de demonstração já existem.');
            return Command::SUCCESS;
        }

        // Códigos e valores fictícios, apenas para demonstração
        $catalog = [
            ['005340-6', 'carro', 'VW - VolksWagen', 'Gol 1.0 Flex 12V 5p', 2020, 'Flex', 52000.00],
            ['002157-2', 'carro', 'Toyota', 'Corolla XEi 2.0 Flex 16V Aut.', 2021, 'Flex', 118000.00],
            ['811099-0', 'moto', 'Honda', 'CG 160 Fan', 2022, 'Flex', 15500.00],
        ];

        $thisMonth = new \DateTimeImmutable('first day of this month 00:00');
        $fipe = [];

        foreach ($catalog as [$code, $type, $brand, $model, $year, $fuel, $base]) {
            $m = new FipeModel(new FipeModelInput($code, $type, $brand, $model, $year, $fuel));
            $this->em->persist($m);

            // 12 meses de histórico: quanto mais antigo, mais caro (depreciação)
            for ($i = 0; $i < 12; $i++) {
                $this->em->persist(new FipePrice($m, $thisMonth->modify("-{$i} months"), round($base * (1 + 0.006 * $i), 2)));
            }
            $fipe[$code] = $m;
        }

        $ads = [
            ['005340-6', 'VW - VolksWagen', 'Gol', '1.0 MPI', 'hatch', 2019, 2020, 'manual', 'Branco', 48000, 4, ['ar-condicionado', 'direção hidráulica'], 'Curitiba', 'PR', 49900.0, 'available'],
            ['002157-2', 'Toyota', 'Corolla', 'XEi 2.0', 'sedan', 2020, 2021, 'automatico', 'Prata', 35000, 4, ['multimídia', 'câmera de ré', 'bancos de couro'], 'São Paulo', 'SP', 129900.0, 'available'],
            ['811099-0', 'Honda', 'CG 160', 'Fan', 'street', 2022, 2022, 'manual', 'Vermelha', 12000, null, [], 'Curitiba', 'PR', 15600.0, 'available'],
            ['005340-6', 'VW - VolksWagen', 'Gol', '1.0 MPI', 'hatch', 2020, 2020, 'manual', 'Preto', 61000, 4, [], 'Londrina', 'PR', 51000.0, 'sold'],
        ];

        foreach ($ads as [$code, $brand, $model, $version, $body, $fab, $year, $transm, $color, $km, $doors, $features, $city, $uf, $price, $status]) {
            $this->em->persist(new Vehicle(new VehicleInput(
                brand: $brand, model: $model, version: $version, bodyType: $body,
                manufactureYear: $fab, modelYear: $year, fuel: 'Flex', transmission: $transm,
                color: $color, mileage: $km, doors: $doors, features: $features,
                city: $city, state: $uf, price: $price, status: $status,
            ), $fipe[$code]));
        }

        $this->em->flush();
        $output->writeln('Dados de demonstração criados.');

        return Command::SUCCESS;
    }
}