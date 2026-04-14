<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminDashboardController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private SupplierRepository $supplierRepository,
        private OrderRepository $orderRepository,
        private ActivityLogRepository $activityLogRepository,
    ) {
    }

    #[Route('/admin', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can access the dashboard.');

            return $this->redirectToRoute('app_admin_products_index');
        }

        $totalUsers = $this->userRepository->count([]);
        $totalStaff = $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_STAFF%')
            ->getQuery()
            ->getSingleScalarResult();
        $totalProducts = $this->productRepository->count([]);
        $totalCategories = $this->categoryRepository->count([]);
        $totalSuppliers = $this->supplierRepository->count([]);

        $orders = $this->orderRepository->findAll();
        $totalOrders = count($orders);
        $totalRevenue = 0.0;
        $customers = [];
        foreach ($orders as $order) {
            $totalRevenue += (float) $order->getTotal();
            $customers[] = strtolower(trim((string) $order->getCustomerEmail()));
        }
        $uniqueCustomers = count(array_unique(array_filter($customers)));

        $recentLogs = $this->activityLogRepository->createQueryBuilder('log')
            ->orderBy('log.timestamp', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalStaff' => $totalStaff,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalSuppliers' => $totalSuppliers,
            'totalOrders' => $totalOrders,
            'totalRevenue' => number_format($totalRevenue, 2, '.', ''),
            'uniqueCustomers' => $uniqueCustomers,
            'recentLogs' => $recentLogs,
        ]);
    }

    #[Route('/staff/dashboard', name: 'app_staff_dashboard')]
    public function staffDashboard(): Response
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            $this->addFlash('error', 'Access denied. Only staff can access the staff dashboard.');
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin_dashboard');
            }

            return $this->redirectToRoute('app_homepage');
        }

        $totalProducts = $this->productRepository->count([]);
        $totalCategories = $this->categoryRepository->count([]);
        $totalSuppliers = $this->supplierRepository->count([]);

        $orders = $this->orderRepository->findAll();
        $totalOrders = count($orders);
        $totalRevenue = 0.0;
        $customers = [];
        foreach ($orders as $order) {
            $totalRevenue += (float) $order->getTotal();
            $customers[] = strtolower(trim((string) $order->getCustomerEmail()));
        }
        $uniqueCustomers = count(array_unique(array_filter($customers)));

        return $this->render('admin/staff_dashboard.html.twig', [
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalSuppliers' => $totalSuppliers,
            'totalOrders' => $totalOrders,
            'totalRevenue' => number_format($totalRevenue, 2, '.', ''),
            'uniqueCustomers' => $uniqueCustomers,
        ]);
    }
}
