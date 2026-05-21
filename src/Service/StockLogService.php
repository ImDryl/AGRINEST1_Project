<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\StockLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class StockLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function logChange(
        Product $product,
        int $previousQuantity,
        int $newQuantity,
        string $changeType,
        ?string $note = null,
        ?int $referenceId = null,
        bool $flush = true,
    ): void {
        if ($previousQuantity === $newQuantity) {
            return;
        }

        $log = new StockLog();
        $log->setProduct($product);
        $log->setProductName((string) $product->getName());
        $log->setChangeType($changeType);
        $log->setPreviousQuantity($previousQuantity);
        $log->setNewQuantity($newQuantity);
        $log->setQuantityChange($newQuantity - $previousQuantity);
        $log->setNote($note);
        $log->setReferenceId($referenceId);

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
            $log->setUsername((string) $user->getEmail());
            $log->setRole($this->resolvePrimaryRole($user->getRoles()));
        } else {
            $log->setUsername('System');
            $log->setRole('System');
        }

        $this->entityManager->persist($log);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function getOriginalQuantity(Product $product): int
    {
        if (!$this->entityManager->contains($product)) {
            return 0;
        }

        $original = $this->entityManager->getUnitOfWork()->getOriginalEntityData($product);

        return (int) ($original['quantity'] ?? $product->getQuantity() ?? 0);
    }

    private function resolvePrimaryRole(array $roles): string
    {
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'Admin';
        }

        if (in_array('ROLE_STAFF', $roles, true)) {
            return 'Staff';
        }

        return 'User';
    }
}
