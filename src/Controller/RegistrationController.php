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

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {

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
            $user->setRoles([]); // Empty array will default to ROLE_USER

            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
    
            // For regular users, redirect to homepage with login modal auto-opened
            $this->addFlash('success', 'Registration successful! Please log in to continue.');
            return $this->redirectToRoute('app_homepage', ['modal' => 'login']);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
