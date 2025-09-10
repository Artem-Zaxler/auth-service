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
        $clientId = $request->query->get('client_id');
        $state = $request->query->get('state');

        return $this->render('oauth2/consent.html.twig', [
            'client_id' => $clientId,
            'scopes' => $request->query->get('scopes'),
            'state' => $state,
            'redirect_uri' => $request->query->get('redirect_uri'),
            'response_type' => $request->query->get('response_type'),
            'request' => $request,
            'grant_type' => $request->query->get('grant_type'),
        ]);
    }

    #[Route('/oauth2/consent/approve', name: 'oauth2_consent_approve')]
    public function approve(Request $request): Response
    {
        $oauthParams = [
            'client_id' => $request->query->get('client_id'),
            'redirect_uri' => $request->query->get('redirect_uri'),
            'response_type' => $request->query->get('response_type'),
            'scopes' => $request->query->get('scopes'),
            'state' => $request->query->get('state'),
            'grant_type' => $request->query->get('grant_type'),
        ];

        $oauthParams = array_filter($oauthParams);

        $oauthParams['approve'] = '1';

        $this->logger->info('Approve request params: ' . json_encode($oauthParams));

        $redirectUrl = $this->generateUrl('oauth2_authorize', $oauthParams);

        return $this->redirect($redirectUrl);
    }

    #[Route('/oauth2/consent/deny', name: 'oauth2_consent_deny')]
    public function deny(Request $request): Response
    {
        $oauthParams = [
            'client_id' => $request->query->get('client_id'),
            'redirect_uri' => $request->query->get('redirect_uri'),
            'response_type' => $request->query->get('response_type'),
            'scopes' => $request->query->get('scopes'),
            'state' => $request->query->get('state'),
            'grant_type' => $request->query->get('grant_type'),
        ];

        $oauthParams = array_filter($oauthParams);

        $oauthParams['deny'] = '1';

        $redirectUrl = $this->generateUrl('oauth2_authorize', $oauthParams);

        return $this->redirect($redirectUrl);
    }
}
