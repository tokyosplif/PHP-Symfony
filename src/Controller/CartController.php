<?php

namespace App\Controller;

use App\Service\CartService;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class CartController extends AbstractController
{
    private CartService $cartService;
    private LoggerInterface $logger;
    private AuthService $authService;

    public function __construct(CartService $cartService, LoggerInterface $logger, AuthService $authService)
    {
        $this->cartService = $cartService;
        $this->logger = $logger;
        $this->authService = $authService;
    }

    #[Route('/cart', name: 'cart_view', methods: ['GET'])]
    public function viewCart(Request $request): Response
    {
        $user = $this->authService->getUserFromSession($request);
        if (!$user) {
            return $this->json(['error' => 'Unauthorized access'], 401);
        }

        $cartDetails = $this->cartService->getCartDetails($user);

        $this->logger->info('Cart viewed', [
            'user' => $user->getId(),
            'cartItems' => count($cartDetails['items']),
        ]);

        return $this->render('cart/view.html.twig', [
            'cart' => $cartDetails,
        ]);
    }

    #[Route('/cart/add/{productId}', name: 'cart_add', methods: ['POST'])]
    public function addProductToCart(Request $request, int $productId): JsonResponse
    {
        $quantity = $request->get('quantity', 1);
        $user = $this->authService->getUserFromSession($request);
        if (!$user) {
            $this->logger->warning('Attempt to add product to cart without authorization');
            return $this->json(['error' => 'Unauthorized access'], 401);
        }

        $this->logger->info('Product added to cart', ['user' => $user->getId(), 'productId' => $productId, 'quantity' => $quantity]);
        $this->cartService->addProductToCart($productId, $quantity, $user);

        $cartDetails = $this->cartService->getCartDetails($user);

        return $this->json([
            'message' => 'Product added to cart',
            'cart' => $cartDetails,
        ]);
    }

    #[Route('/cart/remove/{productId}', name: 'cart_remove', methods: ['POST'])]
    public function removeFromCart(Request $request, int $productId): JsonResponse
    {
        $user = $this->authService->getUserFromSession($request);
        if (!$user) {
            $this->logger->warning('Attempt to remove product from cart without authorization');
            return $this->json(['error' => 'Unauthorized access'], 401);
        }

        $this->logger->info('Product removed from cart', ['user' => $user->getId(), 'productId' => $productId]);
        $this->cartService->removeProductFromCart($productId, $user);

        $cartDetails = $this->cartService->getCartDetails($user);

        return $this->json([
            'message' => 'Product removed from cart',
            'cart' => $cartDetails,
        ]);
    }
}
