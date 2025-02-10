<?php

namespace App\Repository;

use App\Entity\OrderProduct;
use Doctrine\ORM\EntityManagerInterface;

class OrderProductRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $userId
     * @return OrderProduct[]
     */
    public function findProductsByUserId(int $userId): array
    {
        return $this->entityManager->getRepository(OrderProduct::class)
            ->createQueryBuilder('op')
            ->join('op.order', 'o')
            ->andWhere('o.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}
