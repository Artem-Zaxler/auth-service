<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Session;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function create(User $user): Session
    {
        $session = new Session();
        $session->setUserEntity($user);
        $session->setStartedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();

        return $session;
    }

    public function findCurrent(User $user): ?Session
    {
        return $this->createQueryBuilder('s')
            ->where('s.userEntity = :user')
            ->andWhere('s.finishedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('s.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function finishSession(Session $session): void
    {
        $session->setFinishedAt(new \DateTimeImmutable());
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();
    }

    public function getUserSessionsCount(User $user, \DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.userEntity = :user')
            ->setParameter('user', $user);

        if ($dateFrom) {
            $qb->andWhere('s.startedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('s.startedAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getLastUserActivity(User $user): ?\DateTimeImmutable
    {
        return $this->createQueryBuilder('s')
            ->select('MAX(s.startedAt)')
            ->where('s.userEntity = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
