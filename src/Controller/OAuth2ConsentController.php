<?php

namespace App\Controller;

use App\Dto\OAuth2ConsentDto;
use App\Service\OAuth2ConsentService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OAuth2ConsentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ValidatorInterface $validator
    ) {}

    #[Route('/oauth2/consent', name: 'oauth2_consent')]
    public function consent(Request $request, OAuth2ConsentService $consentService): Response
    {
        $this->logger->info('OAuth2ConsentController: consent action started');

        $dto = new OAuth2ConsentDto($request->query->all());

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $this->logger->error('OAuth2 consent validation failed', [
                'errors' => array_map(fn($e) => $e->getMessage(), iterator_to_array($errors))
            ]);
            return new Response('Invalid OAuth2 request', Response::HTTP_BAD_REQUEST);
        }

        if (!$consentService->validateConsentRequest($dto)) {
            return new Response('Invalid OAuth2 request', Response::HTTP_BAD_REQUEST);
        }

        return $this->render('oauth2/consent.html.twig', [
            'client_id' => $dto->clientId,
            'scope' => $dto->scope,
            'state' => $dto->state,
            'redirect_uri' => $dto->redirectUri,
            'response_type' => $dto->responseType,
            'grant_type' => $dto->grantType,
        ]);
    }

    #[Route('/oauth2/consent/approve', name: 'oauth2_consent_approve')]
    public function approve(Request $request, OAuth2ConsentService $consentService): Response
    {
        $this->logger->info('OAuth2ConsentController: approve action started');

        $dto = new OAuth2ConsentDto($request->query->all());

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $this->logger->error('OAuth2 approve validation failed', [
                'errors' => array_map(fn($e) => $e->getMessage(), iterator_to_array($errors))
            ]);
            return new Response('Invalid OAuth2 request', Response::HTTP_BAD_REQUEST);
        }

        if (!$consentService->validateConsentRequest($dto)) {
            return new Response('Invalid OAuth2 request', Response::HTTP_BAD_REQUEST);
        }

        $redirectUrl = $consentService->buildRedirectUrl($dto, true);
        $this->logger->info('OAuth2ConsentController: redirecting to authorization with approval', [
            'url' => $redirectUrl,
        ]);

        return $this->redirect($redirectUrl);
    }

    #[Route('/oauth2/consent/deny', name: 'oauth2_consent_deny')]
    public function deny(Request $request, OAuth2ConsentService $consentService): Response
    {
        $this->logger->info('OAuth2ConsentController: deny action started');

        $dto = new OAuth2ConsentDto($request->query->all());

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $this->logger->error('OAuth2 deny validation failed', [
                'errors' => array_map(fn($e) => $e->getMessage(), iterator_to_array($errors))
            ]);
            return new Response('Invalid OAuth2 request', Response::HTTP_BAD_REQUEST);
        }

        if (!$consentService->validateConsentRequest($dto)) {
            return new Response('Invalid OAuth2 request', Response::HTTP_BAD_REQUEST);
        }

        $redirectUrl = $consentService->buildRedirectUrl($dto, false);
        $this->logger->info('OAuth2ConsentController: redirecting to authorization with denial', [
            'url' => $redirectUrl,
        ]);

        return $this->redirect($redirectUrl);
    }
}
