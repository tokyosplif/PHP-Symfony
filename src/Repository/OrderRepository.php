<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

class OrderRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->entityManager->getRepository(Order::class)->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );
    }

    public function findOneBy(array $criteria): ?Order
    {
        return $this->entityManager->getRepository(Order::class)->findOneBy($criteria);
    }
}
