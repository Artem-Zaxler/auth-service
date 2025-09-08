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

        if ($dto->dateFrom) {
            $qb->andWhere('s.startedAt >= :dateFrom')
                ->setParameter('dateFrom', $dto->dateFrom);
        }

        if ($dto->dateTo) {
            $qb->andWhere('s.startedAt <= :dateTo')
                ->setParameter('dateTo', $dto->dateTo);
        }

        return $qb->getQuery()->getResult();
    }

    public function getUserRegistrationsReport(ReportRequestDto $dto): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.createdAt', 'u.id')
            ->orderBy('u.createdAt', 'DESC');

        if ($dto->dateFrom) {
            $qb->andWhere('u.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dto->dateFrom);
        }

        if ($dto->dateTo) {
            $qb->andWhere('u.createdAt <= :dateTo')
                ->setParameter('dateTo', $dto->dateTo);
        }

        $users = $qb->getQuery()->getResult();

        $registrationsByDate = [];
        foreach ($users as $user) {
            $date = $user['createdAt']->format('Y-m-d');
            if (!isset($registrationsByDate[$date])) {
                $registrationsByDate[$date] = 0;
            }
            $registrationsByDate[$date]++;
        }

        $result = [];
        foreach ($registrationsByDate as $date => $count) {
            $result[] = [
                'date' => $date,
                'registrations' => $count
            ];
        }
        usort($result, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $result;
    }
}
