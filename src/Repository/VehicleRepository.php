<?php

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Vehicle> */
class VehicleRepository extends ServiceEntityRepository
{
    private const SORTS = [
        'newest' => ['v.createdAt', 'DESC'],
        'price_asc' => ['v.price', 'ASC'],
        'price_desc' => ['v.price', 'DESC'],
        'year_desc' => ['v.modelYear', 'DESC'],
        'mileage_asc' => ['v.mileage', 'ASC'],
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    public function search(array $c, int $page, int $limit): Paginator
    {
        $qb = $this->createQueryBuilder('v')->leftJoin('v.fipeModel', 'f')->addSelect('f');

        if ($c['status']) { $qb->andWhere('v.status = :status')->setParameter('status', $c['status']); }
        if ($c['brand']) { $qb->andWhere('v.brand LIKE :brand')->setParameter('brand', '%' . $c['brand'] . '%'); }
        if ($c['model']) { $qb->andWhere('v.model LIKE :model')->setParameter('model', '%' . $c['model'] . '%'); }
        if ($c['fuel']) { $qb->andWhere('v.fuel = :fuel')->setParameter('fuel', $c['fuel']); }
        if ($c['year_min']) { $qb->andWhere('v.modelYear >= :ymin')->setParameter('ymin', $c['year_min']); }
        if ($c['year_max']) { $qb->andWhere('v.modelYear <= :ymax')->setParameter('ymax', $c['year_max']); }
        if ($c['price_min'] !== null) { $qb->andWhere('v.price >= :pmin')->setParameter('pmin', $c['price_min']); }
        if ($c['price_max'] !== null) { $qb->andWhere('v.price <= :pmax')->setParameter('pmax', $c['price_max']); }

        [$field, $dir] = self::SORTS[$c['sort']] ?? self::SORTS['newest'];
        $qb->orderBy($field, $dir)->addOrderBy('v.id', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb->getQuery(), false);
    }
}