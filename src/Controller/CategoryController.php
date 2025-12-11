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

#[Route('/category')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF');
        
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
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

        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $this->activityLogService->logCategoryDelete($category);
            
            $entityManager->remove($category);
            $entityManager->flush();
            
            $this->addFlash('success', 'Category deleted successfully.');
        }

        // Redirect to admin route if user is admin/staff
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_admin_categories_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}

