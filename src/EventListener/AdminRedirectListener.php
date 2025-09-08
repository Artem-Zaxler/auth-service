<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class AdminRedirectListener
{
    public function __construct(
        private RouterInterface $router
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Проверяем, что это 404 ошибка
        if (!$exception instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        /* При любой 404 ошибке в админ-панели 
           перенеправляем на дефолтную страницу списка пользователей */
        if (str_starts_with($path, '/admin')) {
            $response = new RedirectResponse(
                $this->router->generate('admin_users_index')
            );

            $event->setResponse($response);
        }
    }
}
