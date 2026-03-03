<?php

namespace App\Repository;

use App\Entity\ServiceEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceEvent>
 */
class ServiceEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceEvent::class);
    }
    /**
     * @param int[] $planIds
     * @return array<int, ServiceEvent> map planId => openEvent
     */
    public function findOpenEventsByPlanIds(array $planIds): array
    {
        $planIds = array_values(array_filter(array_unique($planIds)));
        if (!$planIds) return [];

        $rows = $this->createQueryBuilder('e')
            ->addSelect('p')
            ->join('e.servicePlan', 'p')
            ->andWhere('p.id IN (:ids)')
            ->andWhere('e.status = :open')
            ->setParameter('ids', $planIds)
            ->setParameter('open', ServiceEvent::STATUS_OPEN)
            ->orderBy('e.serviceDate', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $e) {
            $pid = $e->getServicePlan()?->getId();
            if ($pid && !isset($map[$pid])) $map[$pid] = $e;
        }
        return $map;
    }

    //    /**
    //     * @return ServiceEvent[] Returns an array of ServiceEvent objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ServiceEvent
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
