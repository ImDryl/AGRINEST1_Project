<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;

#[Route('/category')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private CategoryRepository $categoryRepository
    ) {
    }
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF');
        
        return $this->render('category/index.html.twig', [
            'categories' => $this->categoryRepository->findAll(),
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the creator if user is logged in
            if ($this->getUser()) {
                $category->setCreatedBy($this->getUser());
            }

            $entityManager->persist($category);
            $entityManager->flush();

            $this->activityLogService->logCategoryCreate($category);

            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        // Check if user can edit this category (admin or creator)
        if (!$this->isGranted('ROLE_ADMIN') && $category->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only edit categories you created.');
            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
            }
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogService->logCategoryUpdate($category);

            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        // Check if user can delete this category (admin or creator)
        if (!$this->isGranted('ROLE_ADMIN') && $category->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete categories you created.');
            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
            }
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        if (!$this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
            }
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        // Check if category has products
        $products = $category->getProducts();
        $productCount = $products->count();
        
        if ($productCount > 0) {
            // If admin, reassign products to another category
            if ($this->isGranted('ROLE_ADMIN')) {
                // Find another category to reassign products to
                $allCategories = $this->categoryRepository->findAll();
                $otherCategory = null;
                
                foreach ($allCategories as $cat) {
                    if ($cat->getId() !== $category->getId()) {
                        $otherCategory = $cat;
                        break;
                    }
                }
                
                if ($otherCategory === null) {
                    $this->addFlash('error', 'Cannot delete category: This is the only category and it has ' . $productCount . ' product(s). Please create another category first or delete the products.');
                    // Redirect to admin route if user is admin/staff
                    if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                        return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
                    }
                    return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
                }
                
                // Reassign all products to the other category
                foreach ($products as $product) {
                    $product->setCategory($otherCategory);
                }
                $entityManager->flush();
            } else {
                // Staff cannot delete categories with products
                $this->addFlash('error', 'Cannot delete category: This category has ' . $productCount . ' product(s) associated with it. Please reassign or delete the products first.');
                // Redirect to admin route if user is admin/staff
                if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                    return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
                }
                return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        try {
            $this->activityLogService->logCategoryDelete($category);
            
            $entityManager->remove($category);
            $entityManager->flush();
            
            if ($productCount > 0 && $this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('success', 'Category deleted successfully. ' . $productCount . ' product(s) have been reassigned to another category.');
            } else {
                $this->addFlash('success', 'Category deleted successfully.');
            }
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            $this->addFlash('error', 'Cannot delete category: This category has related records that prevent deletion.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Cannot delete category: ' . $e->getMessage());
        }

        // Redirect to admin route if user is admin/staff
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}

