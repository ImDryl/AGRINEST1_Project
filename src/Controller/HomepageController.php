<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class HomepageController extends AbstractController
{
    #[Route('/homepage', name: 'app_homepage')]
    public function index(
        AuthenticationUtils $authenticationUtils,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
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

            $entityManager->persist($user);
            $entityManager->flush();
            
            // For regular users, redirect to homepage with login modal auto-opened
            $this->addFlash('success', 'Registration successful! Please log in to continue.');
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
