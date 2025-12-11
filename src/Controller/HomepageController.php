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

        // Create registration form
        $user = new User();
        $registrationForm = $this->createForm(RegistrationFormType::class, $user);
        $registrationForm->handleRequest($request);

        // Handle registration form submission
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            // Encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $registrationForm->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // Redirect based on role
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            if (in_array('ROLE_STAFF', $roles, true)) {
                return $this->redirectToRoute('app_admin_products_index');
            }
            return $this->redirectToRoute('app_homepage');
        }

        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'HomepageController',
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationForm' => $registrationForm->createView(),
        ]);
    }
}
