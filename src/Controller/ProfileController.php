<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Route('/profile')]
final class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createFormBuilder()
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current Password',
                'mapped' => false,
                'required' => true,
                'attr' => ['placeholder' => 'Enter your current password']
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'New Password',
                    'attr' => ['placeholder' => 'Enter new password']
                ],
                'second_options' => [
                    'label' => 'Repeat New Password',
                    'attr' => ['placeholder' => 'Repeat new password']
                ],
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Change Password',
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            
            // Verify current password
            if (!$userPasswordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->render('profile/change_password.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Hash and set new password
            $newPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $userPasswordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            $entityManager->flush();

            $this->addFlash('success', 'Your password has been changed successfully.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

