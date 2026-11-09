<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $session = $request->getSession();

        // Admin: moadto sa dashboard
        if (in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
            $this->removeTargetPath($session, 'main');

            return new RedirectResponse('/admin/dashboard');
        }

        // Customer: balik sa page nga gusto niya ablihan (kung naa), kung wala sa Home
        $target = $this->getTargetPath($session, 'main');

        if ($target) {
            $this->removeTargetPath($session, 'main');

            return new RedirectResponse($target);
        }

        return new RedirectResponse('/');
    }
}