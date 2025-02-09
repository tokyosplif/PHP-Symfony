<?php

namespace App\Repository;

use App\Entity\CartProduct;
use Doctrine\ORM\EntityManagerInterface;

class CartProductRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findProductsByUser(int $userId): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('cp')
            ->from(CartProduct::class, 'cp')
            ->join('cp.cart', 'c')
            ->where('c.user = :userId')
            ->setParameter('userId', $userId);

        return $qb->getQuery()->getResult();
    }
}
