<?php

namespace App\Controller;

use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): Response {
        // 1. Grab the secret token from the URL (e.g., ?token=abc123xyz)
        $token = $request->query->get('token');

        // 2. If there is no token in the link, kick them back to registration
        if (!$token) {
            $this->addFlash('error', 'Verification token is missing. Please register again.');
            return $this->redirectToRoute('app_register');
        }

        // 3. Pass the token to the Service we built earlier to verify it in the database
        $user = $emailVerificationService->verifyToken($token);

        // 4. If the token is fake or already used, kick them back
        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification link. Please register again.');
            return $this->redirectToRoute('app_register');
        }

        // 5. Success! Tell them they are verified and open the login screen
        $this->addFlash('success', 'Your email has been successfully verified! You can now log in to AgriNest.');
        
        return $this->redirectToRoute('app_homepage', ['modal' => 'login']);
    }
}