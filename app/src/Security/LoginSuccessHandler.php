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
        $roles = $token->getRoleNames();

        // Admin: dashboard
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $this->removeTargetPath($session, 'main');

            return new RedirectResponse('/admin/dashboard');
        }

        // Staff: products management
        if (in_array('ROLE_STAFF', $roles, true)) {
            $this->removeTargetPath($session, 'main');

            return new RedirectResponse('/manage/products');
        }

        // Customer: balik sa gustong ablihan, o Home
        $target = $this->getTargetPath($session, 'main');

        if ($target) {
            $this->removeTargetPath($session, 'main');

            return new RedirectResponse($target);
        }

        return new RedirectResponse('/');
    }
}