<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Session;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;

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
        $em = $this->getEntityManager();

        $em->beginTransaction();

        try {
            // сначала завершение всех предыдущих сессий
            $this->createQueryBuilder('s')
                ->update()
                ->set('s.finishedAt', ':now')
                ->where('s.userEntity = :user')
                ->andWhere('s.finishedAt IS NULL')
                ->setParameter('user', $user)
                ->setParameter('now', new \DateTimeImmutable())
                ->getQuery()
                ->execute();

            $session = new Session();
            $session->setUserEntity($user);
            $session->setStartedAt(new \DateTimeImmutable());

            $em->persist($session);
            $em->flush();
            $em->commit();

            return $session;
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
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
        $em = $this->getEntityManager();

        try {
            $session->setFinishedAt(new \DateTimeImmutable());
            $em->flush();
        } catch (OptimisticLockException $e) {
            throw $e;
        }
    }

    public function getUserSessionsCount(User $user, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): int
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

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getLastUserActivity(User $user): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('s')
            ->select('MAX(s.startedAt) as lastActivity')
            ->where('s.userEntity = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? new \DateTimeImmutable($result) : null;
    }
}
