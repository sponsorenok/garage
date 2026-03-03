<?php
// src/Repository/ServicePlanRepository.php

namespace App\Repository;

use App\Entity\ServicePlan;
use App\Entity\ServiceEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\ServiceEventTask;

final class ServicePlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServicePlan::class);
    }

    /**
     * @return ServicePlan[]
     */
    public function findActiveWithVehicle(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('v')
            ->join('p.vehicle', 'v')
            ->andWhere('p.isActive = true')
            ->orderBy('v.id', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $planIds
     * @return array<int, ServiceEvent> map: planId => lastEvent
     */
    public function findLastEventsByPlanIds(array $planIds): array
    {
        if (!$planIds) return [];

        $em = $this->getEntityManager();
        $rows = $em->createQueryBuilder()
            ->select('e', 'p')
            ->from(ServiceEvent::class, 'e')
            ->join('e.servicePlan', 'p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $planIds)
            ->orderBy('e.serviceDate', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();

        // беремо перший (найновіший) на кожен plan
        $map = [];
        foreach ($rows as $event) {
            $pid = $event->getServicePlan()?->getId();
            if ($pid && !isset($map[$pid])) {
                $map[$pid] = $event;
            }
        }
        return $map;
    }

    /**
     * @return ServicePlan[]
     */
    public function findActiveWithVehicleAndTasks(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('v', 't')
            ->join('p.vehicle', 'v')
            ->leftJoin('p.tasks', 't')
            ->andWhere('p.isActive = true')
            ->orderBy('v.id', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Повертає map: planTaskId => lastDoneEventTask
     *
     * @param int[] $planTaskIds
     * @return array<int, ServiceEventTask>
     */
    public function findLastDoneByPlanTaskIds(array $planTaskIds): array
    {
        $planTaskIds = array_values(array_filter(array_unique($planTaskIds)));
        if (!$planTaskIds) return [];

        $em = $this->getEntityManager();

        // ВАЖЛИВО: беремо тільки DONE, сортуємо по doneDate/doneOdo + id
        $rows = $em->createQueryBuilder()
            ->select('et', 'pt', 'e')
            ->from(ServiceEventTask::class, 'et')
            ->join('et.planTask', 'pt')
            ->join('et.serviceEvent', 'e')
            ->andWhere('pt.id IN (:ids)')
            ->andWhere('et.status = :done')
            ->setParameter('ids', $planTaskIds)
            ->setParameter('done', ServiceEventTask::STATUS_DONE)
            ->orderBy('et.doneDate', 'DESC')
            ->addOrderBy('et.doneOdometerKm', 'DESC')
            ->addOrderBy('et.id', 'DESC')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $et) {
            $ptId = $et->getPlanTask()?->getId();
            if ($ptId && !isset($map[$ptId])) {
                $map[$ptId] = $et; // перший = найновіший
            }
        }
        return $map;
    }

}
