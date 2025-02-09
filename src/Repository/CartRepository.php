<?php

namespace App\Repository;

use App\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;

class CartRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findOneBy(array $criteria)
    {
        return $this->entityManager->getRepository(Cart::class)->findOneBy($criteria);
    }
}
