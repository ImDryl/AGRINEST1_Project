<?php

namespace App\Entity;

use App\Repository\SupplierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
class Supplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Regex(pattern: '/@gmail\.com$/i', message: 'Email must be a Gmail address (ends with @gmail.com).')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Phone number is required.')]
    #[Assert\Regex(pattern: '/^09\d{9}$/', message: 'Phone must start with 09 and be 11 digits.')] 
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Address is required.')]
    private ?string $address = null;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?\App\Entity\User $createdBy = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setSupplier($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getSupplier() === $this) {
                $product->setSupplier(null);
            }
        }

        return $this;
    }

    public function getCreatedBy(): ?\App\Entity\User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?\App\Entity\User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
