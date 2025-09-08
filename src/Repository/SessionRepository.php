<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Session;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry,
        private EntityManagerInterface $em
    ) {
        parent::__construct($registry, Session::class);
    }

    public function create(User $user): Session
    {
        $session = new Session();
        $session->setUserEntity($user);
        $session->setStartedAt(new \DateTimeImmutable());
        $this->em->persist($session);
        $this->em->flush();

        return $session;
    }

    public function findCurrent(User $user): ?Session
    {
        return $this->createQueryBuilder('s')
            ->where('s.userEntity = :user')
            ->andWhere('s.finishedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('s.startedAt', 'DESC')
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function finishSession(Session $session): void
    {
        $session->setFinishedAt(new \DateTimeImmutable());
        $this->em->persist($session);
        $this->em->flush();
    }
}
