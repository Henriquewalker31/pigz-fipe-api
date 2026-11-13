<?php

namespace App\Repository;

use App\Entity\FipePrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FipePrice> */
class FipePriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FipePrice::class);
    }

    /**
     * Último preço de cada modelo em UMA query (evita N+1 na listagem).
     * @param int[] $modelIds
     * @return array<int, FipePrice> indexado pelo id do FipeModel
     */
    public function findLatestForModels(array $modelIds): array
    {
        if (!$modelIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->where('IDENTITY(p.fipeModel) IN (:ids)')
            ->andWhere('p.referenceMonth = (
                SELECT MAX(p2.referenceMonth) FROM App\Entity\FipePrice p2
                WHERE IDENTITY(p2.fipeModel) = IDENTITY(p.fipeModel)
            )')
            ->setParameter('ids', $modelIds)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $price) {
            $map[$price->getFipeModel()->getId()] = $price;
        }

        return $map;
    }
}