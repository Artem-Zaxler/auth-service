<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\Session;
use Psr\Log\LoggerInterface;
use App\Repository\SessionRepository;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(LogoutEvent::class)]
class LogoutListener
{
    public function __construct(
        private SessionRepository $sessionRepository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        $session = $this->sessionRepository->findCurrent($user);
        if (!$session instanceof Session) {
            return;
        }
        $this->sessionRepository->finishSession($session);

        $username = $user->getUsername();
        $sessionStartTime = $session->getStartedAt()->format('h:i:s, d-m-Y');
        $this->logger->info("$username finished his current session at $sessionStartTime");
    }
}
