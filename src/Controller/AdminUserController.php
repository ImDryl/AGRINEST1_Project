<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserEditFormType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AdminUserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private SupplierRepository $supplierRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $userPasswordHasher,
        private ActivityLogService $activityLogService,
    ) {
    }

    #[Route('/admin/users', name: 'app_admin_users_index')]
    public function usersIndex(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can view users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        $selectedRole = (string) $request->query->get('role', 'all');
        if (!in_array($selectedRole, ['all', 'admin', 'staff', 'user'], true)) {
            $selectedRole = 'all';
        }

        $qb = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if ($selectedRole === 'admin') {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%');
        } elseif ($selectedRole === 'staff') {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_STAFF%');
        } elseif ($selectedRole === 'user') {
            $qb->andWhere('u.roles NOT LIKE :adminRole')
                ->andWhere('u.roles NOT LIKE :staffRole')
                ->setParameter('adminRole', '%ROLE_ADMIN%')
                ->setParameter('staffRole', '%ROLE_STAFF%');
        }

        $users = $qb->getQuery()->getResult();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'selectedRole' => $selectedRole,
        ]);
    }

    #[Route('/admin/users/new', name: 'app_admin_user_new')]
    public function userNew(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can create users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'show_roles' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRoles = $form->get('roles')->getData();
            if (!empty($selectedRoles) && !$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('error', 'Access denied. You cannot assign admin or staff roles.');
                return $this->redirectToRoute('app_admin_users_index');
            }

            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->activityLogService->logUserCreate($user);

            $this->addFlash('success', 'User created successfully.');

            return $this->redirectToRoute('app_admin_users_index');
        }

        return $this->render('admin/users/new.html.twig', [
            'userForm' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'app_admin_user_edit')]
    public function userEdit(Request $request, User $user): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can edit users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(UserEditFormType::class, $user, [
            'is_admin' => $isAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('roles')) {
                $selectedRoles = $form->get('roles')->getData();
                if (!empty($selectedRoles) && !$this->isGranted('ROLE_ADMIN')) {
                    $this->addFlash('error', 'Access denied. You cannot change user roles.');
                    return $this->redirectToRoute('app_admin_users_index');
                }
            }

            if (!empty($form->get('plainPassword')->getData())) {
                $user->setPassword(
                    $this->userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }
            $this->entityManager->flush();

            $this->activityLogService->logUserUpdate($user);

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('app_admin_users_index');
        }

        return $this->render('admin/users/edit.html.twig', [
            'userForm' => $form->createView(),
            'user' => $user,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/admin/users/{id}/disable', name: 'app_admin_user_disable', methods: ['POST'])]
    public function userDisable(Request $request, User $user): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can disable users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        if ($this->isCsrfTokenValid('disable' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(false);
            $this->entityManager->flush();

            $details = json_encode(['action' => 'Disable', 'email' => $user->getEmail()], JSON_PRETTY_PRINT);
            $this->activityLogService->log('Disable', 'User', $user->getId(), $details);

            $this->addFlash('success', 'User disabled successfully.');
        }

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/admin/users/{id}/enable', name: 'app_admin_user_enable', methods: ['POST'])]
    public function userEnable(Request $request, User $user): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can enable users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        if ($this->isCsrfTokenValid('enable' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(true);
            $this->entityManager->flush();

            $details = json_encode(['action' => 'Enable', 'email' => $user->getEmail()], JSON_PRETTY_PRINT);
            $this->activityLogService->log('Enable', 'User', $user->getId(), $details);

            $this->addFlash('success', 'User enabled successfully.');
        }

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/admin/users/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function userDelete(Request $request, User $user): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can delete users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $token)) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('app_admin_users_index');
        }

        try {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && $user->getId() === $currentUser->getId()) {
                $this->addFlash('error', 'You cannot delete your own account.');
                return $this->redirectToRoute('app_admin_users_index');
            }

            $products = $this->productRepository->findBy(['createdBy' => $user]);
            foreach ($products as $product) {
                $product->setCreatedBy(null);
            }

            $categories = $this->categoryRepository->findBy(['createdBy' => $user]);
            foreach ($categories as $category) {
                $category->setCreatedBy(null);
            }

            $suppliers = $this->supplierRepository->findBy(['createdBy' => $user]);
            foreach ($suppliers as $supplier) {
                $supplier->setCreatedBy(null);
            }

            $this->entityManager->flush();

            $this->activityLogService->logUserDelete($user);

            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User deleted successfully.');
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Cannot delete user: This user has related records that prevent deletion. Please contact support.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Cannot delete user: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_users_index');
    }
}
