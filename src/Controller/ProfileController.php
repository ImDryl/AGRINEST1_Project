<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ActivityLogService;
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
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
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
        $currentUser = $this->getUser();
        
        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get the managed entity from database -    for updates to work
        $user = $entityManager->getRepository(User::class)->find($currentUser->getId());
        if (!$user) {
            $this->addFlash('error', 'User not found.');
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
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('plainPassword')->getData();
            
            // Verify current password using the original user from security token
            if (!$userPasswordHasher->isPasswordValid($currentUser, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->render('profile/change_password.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Hash and set new password on the managed entity
            $hashedPassword = $userPasswordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            // The user entity is already managed, just flush
            $entityManager->flush();

            // Log the password change
            $this->activityLogService->logPasswordChange($user);

            $this->addFlash('success', 'Your password has been changed successfully.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

