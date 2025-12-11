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
        
        // Get roles from both token and user to ensure we have the correct roles
        $tokenRoles = $token->getRoleNames();
        $userRoles = $user instanceof \App\Entity\User ? $user->getRoles() : [];

        // Check for admin role first (check both token and user roles)
        if (in_array('ROLE_ADMIN', $tokenRoles, true) || in_array('ROLE_ADMIN', $userRoles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        // Check for staff role (check both token and user roles)
        if (in_array('ROLE_STAFF', $tokenRoles, true) || in_array('ROLE_STAFF', $userRoles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_products_index'));
        }

        // Default redirect for regular users
        return new RedirectResponse($this->router->generate('app_homepage'));
    }
}
