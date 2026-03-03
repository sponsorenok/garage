<?php

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /**
     * @param int[] $slotIds
     * @return array<int, Vehicle> map: slotId => vehicle
     */
    public function findByStaffSlotIdsMap(array $slotIds): array
    {
        $slotIds = array_values(array_filter(array_unique($slotIds)));
        if (!$slotIds) return [];

        $rows = $this->createQueryBuilder('v')
            ->addSelect('s', 't', 'd')
            ->join('v.staffSlot', 's')
            ->leftJoin('v.type', 't')
            ->leftJoin('v.department', 'd')
            ->andWhere('s.id IN (:ids)')
            ->setParameter('ids', $slotIds)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $v) {
            $sid = $v->getStaffSlot()?->getId();
            if ($sid) $map[$sid] = $v;
        }
        return $map;
    }

    public function findInDepartmentWithoutSlot(int $departmentId): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.department', 'd')
            ->andWhere('d.id = :did')
            ->andWhere('v.staffSlot IS NULL')
            ->setParameter('did', $departmentId)
            ->orderBy('v.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

}
