<?php

namespace App\Service;

use App\Dto\OAuth2ConsentDto;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OAuth2ConsentService
{
    public function __construct(
        private ClientManagerInterface $clientManager,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {}

    public function validateConsentRequest(OAuth2ConsentDto $dto): bool
    {
        $client = $this->clientManager->find($dto->clientId);
        if (!$client) {
            $this->logger->warning("OAuth2ConsentService: Client not found", ['client_id' => $dto->clientId]);
            return false;
        }
        if (!$client->isActive()) {
            $this->logger->warning("OAuth2ConsentService: Client is inactive", ['client_id' => $dto->clientId]);
            return false;
        }
        return true;
    }

    public function buildRedirectUrl(OAuth2ConsentDto $dto, bool $approved): string
    {
        $params = [
            'client_id' => $dto->clientId,
            'redirect_uri' => $dto->redirectUri,
            'scope' => $dto->scope,
            'state' => $dto->state,
            'response_type' => $dto->responseType,
        ];
        if ($approved) {
            $params['approve'] = '1';
        } else {
            $params['deny'] = '1';
        }
        return $this->urlGenerator->generate('oauth2_authorize', $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
