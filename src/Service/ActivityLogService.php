<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $details = null
    ): void {
        $user = $this->security->getUser();
        
        $log = new ActivityLog();
        
        if ($user instanceof User) {
            $log->setUser($user);
            $log->setUsername($user->getUsername());
            
            // Get the primary role (Admin, Staff, or User)
            $roles = $user->getRoles();
            $primaryRole = 'User';
            if (in_array('ROLE_ADMIN', $roles)) {
                $primaryRole = 'Admin';
            } elseif (in_array('ROLE_STAFF', $roles)) {
                $primaryRole = 'Staff';
            }
            $log->setRole($primaryRole);
        } else {
            $log->setUsername('System');
            $log->setRole('System');
        }
        
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setDetails($details);
        $log->setTimestamp(new \DateTime());
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logProductCreate($product): void
    {
        $details = json_encode([
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'quantity' => $product->getQuantity(),
            'category' => $product->getCategory()?->getName(),
            'supplier' => $product->getSupplier()?->getName(),
        ], JSON_PRETTY_PRINT);
        
        $this->log('Create', 'Product', $product->getId(), $details);
    }

    public function logProductUpdate($product): void
    {
        $details = json_encode([
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'quantity' => $product->getQuantity(),
            'category' => $product->getCategory()?->getName(),
            'supplier' => $product->getSupplier()?->getName(),
        ], JSON_PRETTY_PRINT);
        
        $this->log('Update', 'Product', $product->getId(), $details);
    }

    public function logProductDelete($product): void
    {
        $details = json_encode([
            'name' => $product->getName(),
            'price' => $product->getPrice(),
        ], JSON_PRETTY_PRINT);
        
        $this->log('Delete', 'Product', $product->getId(), $details);
    }

    public function logCategoryCreate($category): void
    {
        $this->log('Create', 'Category', $category->getId(), json_encode(['name' => $category->getName()], JSON_PRETTY_PRINT));
    }

    public function logCategoryUpdate($category): void
    {
        $this->log('Update', 'Category', $category->getId(), json_encode(['name' => $category->getName()], JSON_PRETTY_PRINT));
    }

    public function logCategoryDelete($category): void
    {
        $this->log('Delete', 'Category', $category->getId(), json_encode(['name' => $category->getName()], JSON_PRETTY_PRINT));
    }

    public function logSupplierCreate($supplier): void
    {
        $details = json_encode([
            'name' => $supplier->getName(),
            'email' => $supplier->getEmail(),
            'phone' => $supplier->getPhone(),
        ], JSON_PRETTY_PRINT);
        
        $this->log('Create', 'Supplier', $supplier->getId(), $details);
    }

    public function logSupplierUpdate($supplier): void
    {
        $details = json_encode([
            'name' => $supplier->getName(),
            'email' => $supplier->getEmail(),
            'phone' => $supplier->getPhone(),
        ], JSON_PRETTY_PRINT);
        
        $this->log('Update', 'Supplier', $supplier->getId(), $details);
    }

    public function logSupplierDelete($supplier): void
    {
        $this->log('Delete', 'Supplier', $supplier->getId(), json_encode(['name' => $supplier->getName()], JSON_PRETTY_PRINT));
    }

    public function logUserCreate($user): void
    {
        $details = json_encode([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], JSON_PRETTY_PRINT);
        
        $this->log('Create', 'User', $user->getId(), $details);
    }

    public function logUserUpdate($user): void
    {
        $details = json_encode([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], JSON_PRETTY_PRINT);
        
        $this->log('Update', 'User', $user->getId(), $details);
    }

    public function logUserDelete($user): void
    {
        $this->log('Delete', 'User', $user->getId(), json_encode(['username' => $user->getUsername()], JSON_PRETTY_PRINT));
    }

    public function logLogin($user): void
    {
        $this->log('Login', null, null, json_encode(['username' => $user->getUsername()], JSON_PRETTY_PRINT));
    }

    public function logLogout($user): void
    {
        $this->log('Logout', null, null, json_encode(['username' => $user->getUsername()], JSON_PRETTY_PRINT));
    }
}

