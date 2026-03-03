<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /** @return Document[] */
    public function findForVehicle(int $vehicleId, int $limit = 50): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.vehicle', 'v')
            ->leftJoin('d.assignment', 'a')
            ->leftJoin('a.vehicle', 'av')
            ->addSelect('v', 'a', 'av')
            ->andWhere('v.id = :id OR av.id = :id')
            ->setParameter('id', $vehicleId)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
