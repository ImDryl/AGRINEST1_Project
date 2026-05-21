<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiRegistrationController extends AbstractController
{
    private const MOBILE_APP_CLIENT = 'manlupig-mobile';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationService $emailVerificationService,
        private ValidatorInterface $validator,
        private ActivityLogService $activityLogService,
    ) {}

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json(['success' => false, 'message' => 'Email and password are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['success' => false, 'message' => 'Invalid email address'], 400);
        }

        if (strlen($password) < 6) {
            return $this->json(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
        }

        // Check if email already exists
        $existingEmail = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingEmail) {
            return $this->json(['success' => false, 'message' => 'Email already registered'], 409);
        }

        // Create new user (Removed setUsername)
        $user = new User();
        $user->setEmail($email);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $isMobileApp = $request->headers->get('X-App-Client') === self::MOBILE_APP_CLIENT;

        if ($isMobileApp) {
            $user->setIsVerified(true);
            $user->setVerificationToken(null);
        } else {
            $verificationToken = $this->emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['success' => false, 'message' => 'Validation failed', 'errors' => $errorMessages], 400);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->activityLogService->log('Create', 'User', $user->getId(), json_encode([
            'source' => $isMobileApp ? 'API Register (Mobile App)' : 'API Register',
            'email' => $user->getEmail(),
            'autoVerified' => $isMobileApp,
        ], JSON_PRETTY_PRINT));

        if (!$isMobileApp) {
            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $user->getVerificationToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            try {
                $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);
            } catch (\Exception $e) {
                // Log error
            }
        }

        return $this->json([
            'success' => true,
            'message' => $isMobileApp
                ? 'Registration successful. You can log in now.'
                : 'Registration successful. Please check your email to verify your account.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified(),
                'roles' => $user->getRoles()
            ]
        ], 201);
    }
}