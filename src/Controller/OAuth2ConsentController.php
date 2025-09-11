<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OAuth2ConsentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    #[Route('/oauth2/consent', name: 'oauth2_consent')]
    public function consent(Request $request): Response
    {
        $this->logger->info('OAuth2ConsentController: consent action started');

        $clientId = $request->query->get('client_id');
        $state = $request->query->get('state');
        $scopes = $request->query->get('scope');
        $redirectUri = $request->query->get('redirect_uri');
        $responseType = $request->query->get('response_type');
        $grantType = $request->query->get('grant_type');

        $this->logger->debug('OAuth2ConsentController: consent request parameters', [
            'client_id' => $clientId,
            'state' => $state,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
            'response_type' => $responseType,
            'grant_type' => $grantType,
            'all_query_params' => $request->query->all()
        ]);

        if (empty($clientId)) {
            $this->logger->warning('OAuth2ConsentController: client_id is missing');
            return new Response('Client ID is required', Response::HTTP_BAD_REQUEST);
        }

        if (empty($redirectUri)) {
            $this->logger->warning('OAuth2ConsentController: redirect_uri is missing');
            return new Response('Redirect URI is required', Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info('OAuth2ConsentController: rendering consent form');

        return $this->render('oauth2/consent.html.twig', [
            'client_id' => $clientId,
            'scope' => $scopes,
            'state' => $state,
            'redirect_uri' => $redirectUri,
            'response_type' => $responseType,
            'request' => $request,
            'grant_type' => $grantType,
        ]);
    }

    #[Route('/oauth2/consent/approve', name: 'oauth2_consent_approve')]
    public function approve(Request $request): Response
    {
        $this->logger->info('OAuth2ConsentController: approve action started');

        $oauthParams = [
            'client_id' => $request->query->get('client_id'),
            'redirect_uri' => $request->query->get('redirect_uri'),
            'response_type' => $request->query->get('response_type'),
            'scope' => $request->query->get('scope'),
            'state' => $request->query->get('state'),
            'grant_type' => $request->query->get('grant_type'),
        ];

        $this->logger->debug('OAuth2ConsentController: approve request parameters', [
            'raw_params' => $request->query->all(),
            'filtered_params' => $oauthParams
        ]);

        $oauthParams = array_filter($oauthParams);

        if (empty($oauthParams['client_id'])) {
            $this->logger->error('OAuth2ConsentController: client_id is missing in approve request');
            return new Response('Client ID is required', Response::HTTP_BAD_REQUEST);
        }

        if (empty($oauthParams['redirect_uri'])) {
            $this->logger->error('OAuth2ConsentController: redirect_uri is missing in approve request');
            return new Response('Redirect URI is required', Response::HTTP_BAD_REQUEST);
        }

        $oauthParams['approve'] = '1';

        $this->logger->info('OAuth2ConsentController: redirecting to authorization with approval', [
            'final_params' => $oauthParams,
            'client_id' => $oauthParams['client_id']
        ]);

        $redirectUrl = $this->generateUrl('oauth2_authorize', $oauthParams);

        $this->logger->debug('OAuth2ConsentController: generated redirect URL', [
            'url' => $redirectUrl
        ]);

        return $this->redirect($redirectUrl);
    }

    #[Route('/oauth2/consent/deny', name: 'oauth2_consent_deny')]
    public function deny(Request $request): Response
    {
        $this->logger->info('OAuth2ConsentController: deny action started');

        $oauthParams = [
            'client_id' => $request->query->get('client_id'),
            'redirect_uri' => $request->query->get('redirect_uri'),
            'response_type' => $request->query->get('response_type'),
            'scope' => $request->query->get('scope'),
            'state' => $request->query->get('state'),
            'grant_type' => $request->query->get('grant_type'),
        ];

        $this->logger->debug('OAuth2ConsentController: deny request parameters', [
            'raw_params' => $request->query->all(),
            'filtered_params' => $oauthParams
        ]);

        $oauthParams = array_filter($oauthParams);

        if (empty($oauthParams['client_id'])) {
            $this->logger->error('OAuth2ConsentController: client_id is missing in deny request');
            return new Response('Client ID is required', Response::HTTP_BAD_REQUEST);
        }

        $oauthParams['deny'] = '1';

        $this->logger->info('OAuth2ConsentController: redirecting to authorization with denial', [
            'final_params' => $oauthParams,
            'client_id' => $oauthParams['client_id']
        ]);

        $redirectUrl = $this->generateUrl('oauth2_authorize', $oauthParams);

        $this->logger->debug('OAuth2ConsentController: generated deny redirect URL', [
            'url' => $redirectUrl
        ]);

        return $this->redirect($redirectUrl);
    }
}
