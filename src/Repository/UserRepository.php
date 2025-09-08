<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findAllPaginated(int $page, int $limit): array
    {
        return $this->createQueryBuilder("u")
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder("u")
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function getUserActivityStats(\DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username', 'COUNT(s.id) as sessionCount', 'MAX(s.startedAt) as lastActivity')
            ->leftJoin('u.sessions', 's')
            ->groupBy('u.id');

        if ($dateFrom) {
            $qb->andWhere('s.startedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('s.startedAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb->getQuery()->getResult();
    }

    public function getRegistrationStats(\DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('DATE(u.createdAt) as date', 'COUNT(u.id) as registrations')
            ->groupBy('date')
            ->orderBy('date', 'DESC');

        if ($dateFrom) {
            $qb->andWhere('u.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('u.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb->getQuery()->getResult();
    }
}
