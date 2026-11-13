<?php

namespace App\Repository;

use App\Entity\FipeModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FipeModel> */
class FipeModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FipeModel::class);
    }

    public function search(array $c, int $page, int $limit): Paginator
    {
        $qb = $this->createQueryBuilder('f');

        if ($c['brand']) { $qb->andWhere('f.brand LIKE :brand')->setParameter('brand', '%' . $c['brand'] . '%'); }
        if ($c['model']) { $qb->andWhere('f.model LIKE :model')->setParameter('model', '%' . $c['model'] . '%'); }
        if ($c['fipe_code']) { $qb->andWhere('f.fipeCode = :code')->setParameter('code', $c['fipe_code']); }
        if ($c['model_year']) { $qb->andWhere('f.modelYear = :year')->setParameter('year', $c['model_year']); }
        if ($c['vehicle_type']) { $qb->andWhere('f.vehicleType = :type')->setParameter('type', $c['vehicle_type']); }

        $qb->orderBy('f.brand')->addOrderBy('f.model')->addOrderBy('f.modelYear', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb->getQuery(), false);
    }
}