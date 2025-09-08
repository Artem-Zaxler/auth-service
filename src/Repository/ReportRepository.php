<?php

namespace App\Repository;

use App\Dto\ReportRequestDto;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getUserActivityReport(ReportRequestDto $dto): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username', 'COUNT(s.id) as sessionCount', 'MAX(s.startedAt) as lastActivity')
            ->leftJoin('u.sessions', 's')
            ->groupBy('u.id');

        $this->applyDateFilters($qb, $dto, 's.startedAt');

        return $qb->getQuery()->getResult();
    }

    public function getUserRegistrationsReport(ReportRequestDto $dto): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('DATE(u.createdAt) as date', 'COUNT(u.id) as registrations')
            ->groupBy('date')
            ->orderBy('date', 'DESC');

        $this->applyDateFilters($qb, $dto, 'u.createdAt');

        return $qb->getQuery()->getResult();
    }

    private function applyDateFilters($qb, ReportRequestDto $dto, string $field): void
    {
        if ($dto->dateFrom) {
            $qb->andWhere("$field >= :dateFrom")
                ->setParameter('dateFrom', $dto->dateFrom);
        }

        if ($dto->dateTo) {
            $qb->andWhere("$field <= :dateTo")
                ->setParameter('dateTo', $dto->dateTo);
        }
    }
}
