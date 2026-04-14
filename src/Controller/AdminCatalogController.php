<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminCatalogController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private SupplierRepository $supplierRepository,
    ) {
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
}
