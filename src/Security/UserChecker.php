<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Block login for users who haven't verified their email yet.
        // Admin/staff bypass verification.
        $roles = $user->getRoles();
        $isElevated = in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true);

        if (!$isElevated && !$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Please verify your email before signing in. Check your inbox for the verification link.'
            );
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Your account has been disabled. Please contact an administrator.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No additional checks needed after authentication
    }
}

