<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/product')]
final class ProductController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF');
        
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $product = new Product();
    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('uploads_directory') . '/images',
                    $newFilename
                );
            } catch (FileException $e) {
                // handle exception if something happens during file upload
            }

            // store the file name instead of its contents
            $product->setImage($newFilename);
        }

        // Set the creator if user is logged in
        if ($this->getUser()) {
            $product->setCreatedBy($this->getUser());
        }

        $entityManager->persist($product);
        $entityManager->flush();

        // Log the action
        $this->activityLogService->logProductCreate($product);

        // Redirect to admin route if user is admin/staff
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_admin_products_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF');

    return $this->render('product/new.html.twig', [
        'product' => $product,
        'form' => $form,
        'isAdmin' => $isAdmin,
    ]);
}

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF');
        
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'isAdmin' => $isAdmin,
        ]);
    }

   #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    // Check if user can edit this product (admin or creator)
    if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
        $this->addFlash('error', 'You can only edit products you created.');
        // Redirect to admin route if user is admin/staff
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_admin_products_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Generate new filename
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('uploads_directory') . '/images',
                    $newFilename
                );
            } catch (FileException $e) {
                // handle upload error
            }

            // Optionally delete old image (if exists)
            if ($product->getImage()) {
                $oldImagePath = $this->getParameter('uploads_directory') . '/images/' . $product->getImage();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Set new image name
            $product->setImage($newFilename);
        }

        $entityManager->flush();

        // Log the action
        $this->activityLogService->logProductUpdate($product);

        // Redirect to admin route if user is admin/staff
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_admin_products_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF');

    return $this->render('product/edit.html.twig', [
        'product' => $product,
        'form' => $form,
        'isAdmin' => $isAdmin,
    ]);
}


 #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
{
    // Check if user can delete this product (admin or creator)
    if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
        $this->addFlash('error', 'You can only delete products you created.');
        // Redirect to admin route if user is admin/staff
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_admin_products_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
        // Log the action before deletion
        $this->activityLogService->logProductDelete($product);
        
        $entityManager->remove($product);
        $entityManager->flush();
        
        $this->addFlash('success', 'Product deleted successfully.');
    }

    // Redirect to admin route if user is admin/staff
    if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
        return $this->redirectToRoute('app_admin_products_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
} 


}
