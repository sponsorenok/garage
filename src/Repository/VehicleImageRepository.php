<?php

namespace App\Repository;

use App\Entity\VehicleImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class VehicleImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleImage::class);
    }

    /** @return VehicleImage[] */
    public function findForVehicle(int $vehicleId, int $limit = 50): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.vehicle', 'v')
            ->leftJoin('i.document', 'd')
            ->addSelect('v', 'd')
            ->andWhere('v.id = :id')
            ->setParameter('id', $vehicleId)
            ->orderBy('i.eventDate', 'DESC')
            ->addOrderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
