<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrderService
{
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private CartService $cartService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        OrderRepository        $orderRepository,
        ProductRepository      $productRepository,
        CartService            $cartService,
        EntityManagerInterface $entityManager,
        LoggerInterface        $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->cartService = $cartService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function createOrder(User $user, array $cartItems): Order
    {
        $order = new Order();
        $order->setUser($user);
        $order->setDate(new DateTime());

        $total = 0;
        foreach ($cartItems as $item) {
            $product = $this->getProductById($item['id']);
            if ($product) {
                $total += $product->getPrice() * $item['quantity'];
            }
        }

        $order->setTotal($total);
        $this->logger->info('Order created with total amount: ' . $total);

        $this->entityManager->persist($order);

        foreach ($cartItems as $item) {
            $product = $this->getProductById($item['id']);
            if ($product) {
                $orderProduct = new OrderProduct();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($product);
                $orderProduct->setQuantity($item['quantity']);
                $this->entityManager->persist($orderProduct);
            }
        }

        $this->cartService->clearCart($user, $this->entityManager);
        $this->entityManager->flush();

        return $order;
    }

    public function getOrdersForUser(User $user): array
    {
        return $this->orderRepository->findBy(['user' => $user]);
    }

    public function getOrderDetails(int $id, User $user): ?Order
    {
        return $this->orderRepository->findOneBy(['id' => $id, 'user' => $user]);
    }

    private function getProductById(int $productId): ?Product
    {
        return $this->productRepository->findById($productId);
    }
}
