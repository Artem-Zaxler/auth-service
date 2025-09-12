<?php

namespace App\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
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
        $clientId = 'code-oauth-client';
        $clientName = 'Code OAuth2 Client';
        $clientSecret = 'code-oauth-client_secret';
        $grants = [
            new Grant('authorization_code'),
            new Grant('refresh_token')
        ];
        $redirectUris = [
            new RedirectUri('https://example.com/callback')
        ];
        $scopes = [
            new Scope('user:read'),
            new Scope('user:write')
        ];

        $client = new Client($clientName, $clientId, $clientSecret);
        $client->setAllowPlainTextPkce(false);
        $client->setGrants(...$grants);
        $client->setRedirectUris(...$redirectUris);
        $client->setScopes(...$scopes);

        $manager->persist($client);
        $manager->flush();

        $this->addReference('oauth2-client', $client);
    }
}
