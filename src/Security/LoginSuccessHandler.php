<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        $roles = $user->getRoles();

        // Redirect based on user role
        if (in_array('ROLE_SUPER_ADMIN', $roles) || in_array('ROLE_MARKET_ADMIN', $roles)) {
            return new RedirectResponse($this->router->generate('market_admin_dashboard'));
        }

        if (in_array('ROLE_MERCHANT', $roles)) {
            return new RedirectResponse($this->router->generate('merchant_dashboard'));
        }

        // Default fallback
        return new RedirectResponse($this->router->generate('market_admin_dashboard'));
    }
}
