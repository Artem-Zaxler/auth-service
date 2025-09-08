<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Создание администратора
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'adminpassword')
        );
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsBlocked(false);
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setUpdatedAt(new \DateTimeImmutable());

        $manager->persist($admin);

        // Создание обычного пользователя
        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@example.com');
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'userpassword')
        );
        $user->setRoles(['ROLE_USER']);
        $user->setIsBlocked(false);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $manager->persist($user);

        $manager->flush();
    }
}
