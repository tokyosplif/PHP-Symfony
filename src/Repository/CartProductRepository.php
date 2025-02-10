<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\CartProduct;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class CartProductRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findProductsByUserId(int $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('cp')
            ->from(CartProduct::class, 'cp')
            ->join('cp.cart', 'c')
            ->where('c.user = :userId')
            ->setParameter('userId', $userId);

        return $qb->getQuery()->getResult();
    }

    public function findByCartAndProduct(Cart $cart, Product $product): ?CartProduct
    {
        return $this->entityManager->getRepository(CartProduct::class)
            ->findOneBy(['cart' => $cart, 'product' => $product]);
    }
}
