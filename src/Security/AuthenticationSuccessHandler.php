<?php

namespace App\Security;

use App\Service\ActivityLogService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;
    private $activityLogService;

    public function __construct(
        RouterInterface $router,
        ActivityLogService $activityLogService
    ) {
        $this->router = $router;
        $this->activityLogService = $activityLogService;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();
        
        // Log the login action
        if ($user instanceof \App\Entity\User) {
            $this->activityLogService->logLogin($user);
        }
        
        $roles = $token->getRoleNames();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        if (in_array('ROLE_STAFF', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_products_index'));
        }

        return new RedirectResponse($this->router->generate('app_homepage'));
    }
}
