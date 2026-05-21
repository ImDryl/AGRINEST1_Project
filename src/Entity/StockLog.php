<?php

namespace App\Entity;

use App\Repository\StockLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockLogRepository::class)]
#[ORM\Table(name: 'stock_log')]
#[ORM\Index(columns: ['product_id'], name: 'idx_stock_log_product')]
#[ORM\Index(columns: ['change_type'], name: 'idx_stock_log_change_type')]
#[ORM\Index(columns: ['created_at'], name: 'idx_stock_log_created_at')]
class StockLog
{
    public const TYPE_INITIAL = 'initial';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_ORDER = 'order';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 50)]
    private ?string $role = null;

    #[ORM\Column(length: 50)]
    private ?string $changeType = null;

    #[ORM\Column]
    private ?int $previousQuantity = null;

    #[ORM\Column]
    private ?int $newQuantity = null;

    #[ORM\Column]
    private ?int $quantityChange = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(nullable: true)]
    private ?int $referenceId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getChangeType(): ?string
    {
        return $this->changeType;
    }

    public function setChangeType(string $changeType): static
    {
        $this->changeType = $changeType;

        return $this;
    }

    public function getPreviousQuantity(): ?int
    {
        return $this->previousQuantity;
    }

    public function setPreviousQuantity(int $previousQuantity): static
    {
        $this->previousQuantity = $previousQuantity;

        return $this;
    }

    public function getNewQuantity(): ?int
    {
        return $this->newQuantity;
    }

    public function setNewQuantity(int $newQuantity): static
    {
        $this->newQuantity = $newQuantity;

        return $this;
    }

    public function getQuantityChange(): ?int
    {
        return $this->quantityChange;
    }

    public function setQuantityChange(int $quantityChange): static
    {
        $this->quantityChange = $quantityChange;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function setReferenceId(?int $referenceId): static
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
