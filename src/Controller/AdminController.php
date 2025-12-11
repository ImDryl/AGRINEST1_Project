<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserEditFormType;
use App\Repository\ActivityLogRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminController extends AbstractController
{
    private $userRepository;
    private $productRepository;
    private $categoryRepository;
    private $supplierRepository;
    private $activityLogRepository;
    private $entityManager;
    private $userPasswordHasher;
    private $activityLogService;

    public function __construct(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SupplierRepository $supplierRepository,
        ActivityLogRepository $activityLogRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        ActivityLogService $activityLogService
    ) {
        $this->userRepository = $userRepository;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->supplierRepository = $supplierRepository;
        $this->activityLogRepository = $activityLogRepository;
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->activityLogService = $activityLogService;
    }

    #[Route('/admin', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        // Only admin can access dashboard
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can access the dashboard.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        $totalUsers = $this->userRepository->count([]);
        $totalProducts = $this->productRepository->count([]);
        $totalCategories = $this->categoryRepository->count([]);
        $totalSuppliers = $this->supplierRepository->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalSuppliers' => $totalSuppliers,
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users_index')]
    public function usersIndex(): Response
    {
        // Only admin can access user list
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can view users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        $users = $this->userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/products', name: 'app_admin_products_index')]
    public function productsIndex(): Response
    {
        $products = $this->productRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'isAdmin' => true,
        ]);
    }

    #[Route('/admin/categories', name: 'app_admin_categories_index')]
    public function categoriesIndex(): Response
    {
        $categories = $this->categoryRepository->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
            'isAdmin' => true,
        ]);
    }

    #[Route('/admin/suppliers', name: 'app_admin_suppliers_index')]
    public function suppliersIndex(): Response
    {
        $suppliers = $this->supplierRepository->findAll();

        return $this->render('supplier/index.html.twig', [
            'suppliers' => $suppliers,
            'isAdmin' => true,
        ]);
    }

    #[Route('/admin/users/new', name: 'app_admin_user_new')]
    public function userNew(Request $request): Response
    {
        // Only admin can create users
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can create users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Security check: Prevent staff/admin role assignment if somehow bypassed
            $selectedRoles = $form->get('roles')->getData();
            if (!empty($selectedRoles) && !$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('error', 'Access denied. You cannot assign admin or staff roles.');
                return $this->redirectToRoute('app_admin_users_index');
            }

            // encode the plain password
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
        // Only admin can edit users
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
            // Security check: Prevent role changes if somehow bypassed
            if ($form->has('roles')) {
                $selectedRoles = $form->get('roles')->getData();
                if (!empty($selectedRoles) && !$this->isGranted('ROLE_ADMIN')) {
                    $this->addFlash('error', 'Access denied. You cannot change user roles.');
                    return $this->redirectToRoute('app_admin_users_index');
                }
            }

            // Only update password if a new one is provided
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

    #[Route('/admin/users/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function userDelete(Request $request, User $user): Response
    {
        // Only admin can delete users
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can delete users.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->activityLogService->logUserDelete($user);
            
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/admin/logs', name: 'app_admin_logs_index')]
    public function logsIndex(Request $request): Response
    {
        // Only admin can view logs
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can view activity logs.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        // Get filter parameters
        $username = $request->query->get('username', '');
        $action = $request->query->get('action', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');

        // Build query
        $qb = $this->activityLogRepository->createQueryBuilder('log')
            ->orderBy('log.timestamp', 'DESC');

        if ($username) {
            $qb->andWhere('log.username LIKE :username')
                ->setParameter('username', '%' . $username . '%');
        }

        if ($action) {
            $qb->andWhere('log.action = :action')
                ->setParameter('action', $action);
        }

        if ($dateFrom) {
            $qb->andWhere('log.timestamp >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo) {
            $dateToObj = new \DateTime($dateTo);
            $dateToObj->setTime(23, 59, 59);
            $qb->andWhere('log.timestamp <= :dateTo')
                ->setParameter('dateTo', $dateToObj);
        }

        // Get all usernames and actions for filter dropdowns
        $allUsernames = $this->activityLogRepository->createQueryBuilder('log')
            ->select('DISTINCT log.username')
            ->orderBy('log.username', 'ASC')
            ->getQuery()
            ->getResult();

        $allActions = ['Create', 'Update', 'Delete', 'Login', 'Logout'];

        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $totalLogs = count($qb->getQuery()->getResult());
        $logs = $qb->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        $totalPages = ceil($totalLogs / $limit);

        return $this->render('admin/logs/index.html.twig', [
            'logs' => $logs,
            'username' => $username,
            'action' => $action,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'allUsernames' => array_column($allUsernames, 'username'),
            'allActions' => $allActions,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs,
        ]);
    }
}