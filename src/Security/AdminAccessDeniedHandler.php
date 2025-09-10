<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AdminAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?RedirectResponse
    {
        if (str_starts_with($request->getPathInfo(), '/admin')) {
            $session = $request->getSession();
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add('error', 'У вас нет прав доступа к админ-панели.');
            }

            $loginUrl = $this->urlGenerator->generate('admin_auth_login');
            return new RedirectResponse($loginUrl);
        }

        return null;
    }
}
