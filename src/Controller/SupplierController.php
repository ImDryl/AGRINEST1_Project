<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Repository\SupplierRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/supplier')]
final class SupplierController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    #[Route(name: 'app_supplier_index', methods: ['GET'])]
    public function index(SupplierRepository $supplierRepository): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF');
        
        return $this->render('supplier/index.html.twig', [
            'suppliers' => $supplierRepository->findAll(),
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/new', name: 'app_supplier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $supplier = new Supplier();
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the creator if user is logged in
            if ($this->getUser()) {
                $supplier->setCreatedBy($this->getUser());
            }

            $entityManager->persist($supplier);
            $entityManager->flush();

            $this->activityLogService->logSupplierCreate($supplier);

            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_suppliers_index');
            }

            return $this->redirectToRoute('app_supplier_index');
        }

       
        return $this->render('supplier/new.html.twig', [
            'supplier' => $supplier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_supplier_show', methods: ['GET'])]
    public function show(Supplier $supplier): Response
    {
        return $this->render('supplier/show.html.twig', [
            'supplier' => $supplier,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_supplier_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Supplier $supplier, EntityManagerInterface $entityManager): Response
    {
        // Check if user can edit this supplier (admin, staff, or creator)
        // Staff can edit any supplier, but regular users can only edit their own
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF') && $supplier->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only edit suppliers you created.');
            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_suppliers_index');
            }
            return $this->redirectToRoute('app_supplier_index');
        }

        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogService->logSupplierUpdate($supplier);

            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_suppliers_index');
            }

            return $this->redirectToRoute('app_supplier_index');
        }

        
        return $this->render('supplier/edit.html.twig', [
            'supplier' => $supplier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_supplier_delete', methods: ['POST'])]
    public function delete(Request $request, Supplier $supplier, EntityManagerInterface $entityManager): Response
    {
        // Check if user can delete this supplier (admin, staff, or creator)
        // Staff can delete any supplier, but regular users can only delete their own
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF') && $supplier->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete suppliers you created.');
            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_suppliers_index');
            }
            return $this->redirectToRoute('app_supplier_index');
        }

        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $supplier->getId(), $token)) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            // Redirect to admin route if user is admin/staff
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_admin_suppliers_index');
            }
            return $this->redirectToRoute('app_supplier_index');
        }

        try {
            // Set all products' supplier to NULL before deleting
            $products = $supplier->getProducts();
            foreach ($products as $product) {
                $product->setSupplier(null);
            }
            
            // Flush changes to products first
            $entityManager->flush();
            
            $this->activityLogService->logSupplierDelete($supplier);
            
            $entityManager->remove($supplier);
            $entityManager->flush();
            
            $this->addFlash('success', 'Supplier deleted successfully.');
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            $this->addFlash('error', 'Cannot delete supplier: This supplier has related records that prevent deletion.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Cannot delete supplier: ' . $e->getMessage());
        }

        // Redirect to admin route if user is admin/staff
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_admin_suppliers_index');
        }

        return $this->redirectToRoute('app_supplier_index');
    }
}
