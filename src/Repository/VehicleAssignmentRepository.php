<?php

namespace App\Repository;

use App\Entity\Vehicle;
use App\Entity\VehicleAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class VehicleAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleAssignment::class);
    }

    public function findActiveByVehicle(Vehicle $vehicle): ?VehicleAssignment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.vehicle = :v')
            ->andWhere('a.isActive = true')
            ->setParameter('v', $vehicle)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return array<int, VehicleAssignment> map: vehicleId => assignment */
    public function findActiveMapByVehicles(array $vehicleIds): array
    {
        $vehicleIds = array_values(array_filter(array_unique(array_map('intval', $vehicleIds))));
        if (!$vehicleIds) return [];

        $rows = $this->createQueryBuilder('a')
            ->addSelect('v')
            ->join('a.vehicle', 'v')
            ->andWhere('v.id IN (:ids)')
            ->andWhere('a.isActive = true')
            ->setParameter('ids', $vehicleIds)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $a) {
            $vid = $a->getVehicle()?->getId();
            if ($vid) $map[$vid] = $a;
        }
        return $map;
    }
}
