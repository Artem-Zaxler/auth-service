<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use League\Bundle\OAuth2ServerBundle\Entity\Client;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OAuth2ClientFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $clientId = 'test_client';
        $client = new Client($clientId, 'Test Client');
        $client->setAllowPlainTextPkce(false);
        $client->supportsGrantType('client_credentials');
        $client->$client->setSecret('test_secret');

        $manager->persist($client);
        $manager->flush();

        $this->addReference('oauth2-client', $client);
    }
}
