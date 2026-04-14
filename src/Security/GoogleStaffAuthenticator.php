<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleStaffAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private AuthenticationSuccessHandler $authenticationSuccessHandler,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);
        $googleUser = $client->fetchUserFromToken($accessToken);
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));

        if ($email === '') {
            throw new CustomUserMessageAuthenticationException('Google account email is required.');
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
        }

        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);
        $isStaff = in_array('ROLE_STAFF', $roles, true);
        $needsFlush = false;

        // Auto-promote Google sign-ins to staff when they are not admin/staff yet.
        if (!$isAdmin && !$isStaff) {
            $user->setRoles(['ROLE_STAFF']);
            $needsFlush = true;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Your account is disabled. Please contact an administrator.');
        }

        if (!$user->isVerified()) {
            $user->setIsVerified(true);
            $user->setVerificationToken(null);
            $needsFlush = true;
        }

        if ($needsFlush) {
            $this->entityManager->flush();
        }

        return new SelfValidatingPassport(
            new UserBadge($email, static fn () => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->authenticationSuccessHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception->getMessageKey();
        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('error', $message);
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
