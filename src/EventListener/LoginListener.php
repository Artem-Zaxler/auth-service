<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\SessionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

#[AsEventListener(InteractiveLoginEvent::class)]
class LoginListener
{
    public function __construct(
        private SessionRepository $sessionRepository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof User) {
            return;
        }
        $session = $this->sessionRepository->create($user);

        $username = $user->getUsername();
        $sessionStartTime = $session->getStartedAt()->format('h:i:s, d-m-Y');
        $this->logger->info("$username started new session at $sessionStartTime");
    }
}
