<?php

namespace App\EventSubscriber;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OAuth2AuthorizationSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router,
        private RequestStack $requestStack,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AuthorizationRequestResolveEvent::class => 'onAuthorizationRequestResolve',
        ];
    }

    public function onAuthorizationRequestResolve(AuthorizationRequestResolveEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $this->logger->info('Authorization request params: ' . json_encode($request->query->all()));

        if ($request->query->get('approve') === '1') {
            $event->resolveAuthorization(true);
            return;
        }

        if ($request->query->get('deny') === '1') {
            $event->resolveAuthorization(false);
            return;
        }

        $redirectUri = $this->router->generate('oauth2_consent', [
            'client_id' => $event->getClient()->getIdentifier(),
            'scopes' => implode(',', $event->getScopes()),
            'state' => $event->getState(),
            'redirect_uri' => $event->getRedirectUri(),
            'response_type' => 'code',
            'grant_type' => $event->getGrantTypeId()
        ]);

        $event->setResponse(new RedirectResponse($redirectUri));
    }
}
