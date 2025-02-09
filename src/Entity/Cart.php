<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'cart')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: CartProduct::class, mappedBy: 'cart', cascade: ['persist', 'remove'])]
    private Collection $cartProducts;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total = '0.00';

    public function __construct()
    {
        $this->cartProducts = new ArrayCollection();
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): void
    {
        $this->total = $total;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCartProducts(): Collection
    {
        return $this->cartProducts;
    }

    public function updateTotal(): void
    {
        $total = '0.00';

        foreach ($this->getCartProducts() as $cartProduct) {
            $subtotal = bcmul((string) $cartProduct->getProduct()->getPrice(), (string) $cartProduct->getQuantity(), 2);
            $total = bcadd($total, $subtotal, 2);
        }

        $this->total = $total;
    }
}
