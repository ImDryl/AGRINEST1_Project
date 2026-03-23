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
    private const FIREWALL_NAME = 'main';

    public function __construct(
        private RouterInterface $router,
        private ActivityLogService $activityLogService,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();

        if ($user instanceof \App\Entity\User) {
            $this->activityLogService->logLogin($user);
        }

        $tokenRoles = $token->getRoleNames();
        $userRoles = $user instanceof \App\Entity\User ? $user->getRoles() : [];

        if (in_array('ROLE_ADMIN', $tokenRoles, true) || in_array('ROLE_ADMIN', $userRoles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        if (in_array('ROLE_STAFF', $tokenRoles, true) || in_array('ROLE_STAFF', $userRoles, true)) {
            return new RedirectResponse($this->router->generate('app_staff_dashboard'));
        }

        // Regular users: go to intended URL (saved when they tried to open /product while logged out)
        $targetPath = $this->getAndRemoveTargetPath($request);
        if ($targetPath !== null) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_product_index'));
    }

    private function getAndRemoveTargetPath(Request $request): ?string
    {
        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session !== null) {
            $sessionKey = '_security.'.self::FIREWALL_NAME.'.target_path';
            if ($session->has($sessionKey)) {
                $path = $session->get($sessionKey);
                $session->remove($sessionKey);
                if (is_string($path) && $this->isSafeRelativePath($path)) {
                    return $path;
                }
            }
        }

        $postTarget = $request->request->get('_target_path');
        if (is_string($postTarget) && $this->isSafeRelativePath($postTarget)) {
            return $postTarget;
        }

        return null;
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || !str_starts_with($path, '/')) {
            return false;
        }
        if (str_starts_with($path, '//')) {
            return false;
        }

        return true;
    }
}
