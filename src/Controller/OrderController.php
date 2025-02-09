<?php

namespace App\Controller;

use App\Service\OrderService;
use App\Service\CartService;
use App\Service\AuthService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class OrderController extends AbstractController
{
    private OrderService $orderService;
    private CartService $cartService;
    private AuthService $authService;
    private LoggerInterface $logger;

    public function __construct(
        OrderService $orderService,
        CartService $cartService,
        AuthService $authService,
        LoggerInterface $logger
    ) {
        $this->orderService = $orderService;
        $this->cartService = $cartService;
        $this->authService = $authService;
        $this->logger = $logger;
    }

    #[Route('/order', name: 'order_submit', methods: ['POST'])]
    public function submitOrder(Request $request): JsonResponse
    {
        $user = $this->authService->getUserFromSession($request);
        if (!$user) {
            $this->logger->warning('Unauthorized access attempt for order submission.', [
                'ip' => $request->getClientIp(),
            ]);
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $cartDetails = $this->cartService->getCartDetails($user);
        if (empty($cartDetails['items'])) {
            $this->logger->info('Order submission attempt with empty cart.', [
                'user_id' => $user->getId(),
            ]);
            return $this->json(['error' => 'Cart is empty'], 400);
        }

        try {
            $order = $this->orderService->createOrder($user, $cartDetails['items']);
            $this->logger->info('Order placed successfully.', [
                'user_id' => $user->getId(),
                'order_id' => $order->getId(),
                'total' => $order->getTotal(),
            ]);

            return $this->json([
                'message' => 'Order placed successfully',
                'order_id' => $order->getId(),
                'total' => $order->getTotal(),
            ]);
        } catch (Exception $e) {
            $this->logger->error('Error processing order.', [
                'user_id' => $user->getId(),
                'exception' => $e->getMessage(),
            ]);
            return $this->json(['error' => 'Order processing error', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/orders', name: 'orders_list', methods: ['GET'])]
    public function listOrders(Request $request): JsonResponse
    {
        $user = $this->authService->getUserFromSession($request);
        if (!$user) {
            $this->logger->warning('Unauthorized access attempt for order list.', [
                'ip' => $request->getClientIp(),
            ]);
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $orders = $this->orderService->getOrdersForUser($user);
            $this->logger->info('Orders retrieved for user.', [
                'user_id' => $user->getId(),
                'orders_count' => count($orders),
            ]);

            return $this->json(array_map(fn($order) => [
                'order_id' => $order->getId(),
                'date' => $order->getDate()->format('Y-m-d H:i:s'),
                'total' => $order->getTotal(),
            ], $orders));
        } catch (Exception $e) {
            $this->logger->error('Error retrieving orders.', [
                'user_id' => $user->getId(),
                'exception' => $e->getMessage(),
            ]);
            return $this->json(['error' => 'Error retrieving orders'], 500);
        }
    }

    #[Route('/order/{id}', name: 'order_view', methods: ['GET'])]
    public function viewOrder(Request $request, int $id): JsonResponse
    {
        $user = $this->authService->getUserFromSession($request);
        if (!$user) {
            $this->logger->warning('Unauthorized access attempt for order view.', [
                'ip' => $request->getClientIp(),
                'order_id' => $id,
            ]);
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $orderDetails = $this->orderService->getOrderDetails($id, $user);
            $this->logger->info('Order details viewed.', [
                'user_id' => $user->getId(),
                'order_id' => $id,
            ]);
            return $this->json($orderDetails);
        } catch (Exception $e) {
            $this->logger->error('Error retrieving order details.', [
                'user_id' => $user->getId(),
                'order_id' => $id,
                'exception' => $e->getMessage(),
            ]);
            return $this->json(['error' => 'Error retrieving order details'], 500);
        }
    }
}
