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
use Symfony\Component\Form\FormError;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
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
            return $this->redirectToRoute('app_homepage');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'show_roles' => false, // Hide roles field on registration page
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $user->getEmail(),
            ]);
            if ($existingUser) {
                $form->get('email')->addError(new FormError('This email is already registered. Please sign in instead.'));

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Force ROLE_USER for registration page (security)
            $user->setRoles(['ROLE_USER']); 

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

            // 4. Send verification email
            try {
                $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
                $this->addFlash('success', 'Registration successful! Verification email sent. Check Inbox/Spam, then sign in.');
            } catch (TransportExceptionInterface $e) {
                $this->addFlash(
                    'warning',
                    'Account created, but the verification email was not sent (SMTP error). In Brevo open Transactional → SMTP & API → SMTP and copy the exact "Login" from the same row as your SMTP key into MAILER_SMTP_USER in .env — it is usually not your Gmail address.'
                );
                if ($this->getParameter('kernel.debug')) {
                    $this->addFlash('warning', $e->getMessage());
                }
            } catch (\RuntimeException $e) {
                $this->addFlash('warning', $e->getMessage());
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}