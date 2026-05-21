<?php

namespace App\Controller;

use App\Service\ActivityLogService;
use App\Service\GoogleIdTokenVerifier;
use App\Service\GoogleUserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class ApiGoogleAuthController extends AbstractController
{
    public function __construct(
        private GoogleIdTokenVerifier $googleIdTokenVerifier,
        private GoogleUserService $googleUserService,
        private JWTTokenManagerInterface $jwtManager,
        private ActivityLogService $activityLogService,
    ) {
    }

    #[Route('/auth/google', name: 'api_auth_google', methods: ['POST'])]
    public function googleAuth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $idToken = (string) ($data['idToken'] ?? '');

        try {
            $email = $this->googleIdTokenVerifier->verifyAndGetEmail($idToken);
            $user = $this->googleUserService->provisionUserFromGoogleEmail($email);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\RuntimeException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

        $jwt = $this->jwtManager->create($user);

        $this->activityLogService->log('Login', 'User', $user->getId(), json_encode([
            'source' => 'API Google Auth (Mobile)',
            'email' => $user->getEmail(),
        ], JSON_PRETTY_PRINT));

        return $this->json([
            'success' => true,
            'message' => 'Google sign-in successful.',
            'data' => [
                'token' => $jwt,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'verified' => $user->isVerified(),
                ],
            ],
        ]);
    }
}
