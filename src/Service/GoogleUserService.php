<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creates or updates Agrinest users for Google sign-in (web OAuth or mobile ID token).
 */
class GoogleUserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function provisionUserFromGoogleEmail(string $email): User
    {
        $email = mb_strtolower(trim($email));

        if ($email === '') {
            throw new \InvalidArgumentException('Google account email is required.');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            $user = (new User())
                ->setEmail($email)
                ->setRoles(['ROLE_STAFF'])
                ->setIsActive(true)
                ->setIsVerified(true)
                ->setVerificationToken(null);
            $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $user;
        }

        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);
        $isStaff = in_array('ROLE_STAFF', $roles, true);
        $needsFlush = false;

        if (!$isAdmin && !$isStaff) {
            $user->setRoles(['ROLE_STAFF']);
            $needsFlush = true;
        }

        if (!$user->isActive()) {
            throw new \RuntimeException('Your account is disabled. Please contact an administrator.');
        }

        if (!$user->isVerified()) {
            $user->setIsVerified(true);
            $user->setVerificationToken(null);
            $needsFlush = true;
        }

        if ($needsFlush) {
            $this->entityManager->flush();
        }

        return $user;
    }
}
