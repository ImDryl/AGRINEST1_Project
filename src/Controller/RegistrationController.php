<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EmailVerificationService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService
    ): Response {

         if ($this->getUser()) {
            return $this->redirectToRoute('app_register');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'show_roles' => false, // Hide roles field on registration page
        ]);
        $form->handleRequest($request);
if ($form->isSubmitted() && $form->isValid()) {
            // Force ROLE_USER for registration page (security)
            $user->setRoles([]); 

            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // 1. Generate token and set status FIRST
            $verificationToken = $emailVerificationService->generateVerificationToken();
            $user->setIsVerified(false);
            $user->setVerificationToken($verificationToken);
            
            // 2. NOW save everything to the database at the exact same time!
            $entityManager->persist($user);
            $entityManager->flush(); 

            // 3. Generate the click-able link for the email
            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $verificationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // 4. Actually send the email via Brevo
            $emailVerificationService->sendVerificationEmail($user, $verificationUrl);

            // 5. Tell the user to check their inbox
            $this->addFlash('success', 'Registration successful! Please check your email to verify your account before logging in.');
            return $this->redirectToRoute('app_homepage', ['modal' => 'login']);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}