<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(
        AuthenticationUtils $authenticationUtils,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService,
    ): Response {
        // Get login error and last username
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Create registration form (without roles field for homepage)
        $user = new User();
        $registrationForm = $this->createForm(RegistrationFormType::class, $user, [
            'show_roles' => false, // Hide roles field on homepage
        ]);
        $registrationForm->handleRequest($request);

        // Handle registration form submission
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            // Force ROLE_USER for homepage registrations (security)
            $user->setRoles([]); // Empty array will default to ROLE_USER
            
            // Encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $registrationForm->get('plainPassword')->getData()
                )
            );

            $user->setIsVerified(false);
            $user->setVerificationToken($emailVerificationService->generateVerificationToken());

            $entityManager->persist($user);
            $entityManager->flush();

            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $user->getVerificationToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            try {
                $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
                $this->addFlash('success', sprintf(
                    'Registration successful! Verification email sent to %s. Check Inbox/Spam, then sign in.',
                    $user->getEmail()
                ));
            } catch (TransportExceptionInterface) {
                $this->addFlash('warning', 'Account created, but we could not send the verification email. Please contact support.');
            }

            // For regular users, redirect to homepage with login modal auto-opened
            return $this->redirectToRoute('app_homepage', ['modal' => 'login']);
        }

        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'HomepageController',
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationForm' => $registrationForm->createView(),
        ]);
    }
}
