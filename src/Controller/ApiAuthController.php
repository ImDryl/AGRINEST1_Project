<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class ApiAuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    #[Route('/login_check', name: 'api_login_check', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json([
                'success' => false,
                'message' => 'Email and password are required.',
            ], 400);
        }

        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (!$user->isActive()) {
            return $this->json([
                'success' => false,
                'message' => 'Your account is disabled. Please contact support.',
            ], 403);
        }

        if (!$user->isVerified()) {
            return $this->json([
                'success' => false,
                'message' => 'Please verify your email address before logging in.',
            ], 403);
        }

        $jwt = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $jwt,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'verified' => $user->isVerified(),
                ],
            ],
        ], 200);
    }
}

