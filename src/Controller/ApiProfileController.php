<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/profile')]
final class ApiProfileController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_profile_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $user = $this->requireUser();

        return $this->json([
            'success' => true,
            'message' => 'Profile fetched successfully.',
            'data' => $this->serializeUser($user),
            'errors' => [],
        ]);
    }

    #[Route('', name: 'api_profile_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '') {
            return $this->json([
                'success' => false,
                'message' => 'Email is required.',
                'errors' => ['Email is required.'],
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
                'errors' => ['Invalid email.'],
            ], 400);
        }

        $existing = $this->userRepository->findOneBy(['email' => $email]);
        if ($existing instanceof User && $existing->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'This email is already registered.',
                'errors' => ['Email already in use.'],
            ], 409);
        }

        $user->setEmail($email);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $this->serializeUser($user),
            'errors' => [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        $roles = $user->getRoles();
        $roleLabel = 'Customer';
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $roleLabel = 'Admin';
        } elseif (in_array('ROLE_STAFF', $roles, true)) {
            $roleLabel = 'Staff';
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $roles,
            'roleLabel' => $roleLabel,
            'verified' => $user->isVerified(),
            'isActive' => $user->isActive(),
        ];
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        return $user;
    }
}
