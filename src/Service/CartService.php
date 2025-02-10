<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartProduct;
use App\Entity\User;
use App\Repository\AuthRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;

class CartService
{
    private CartRepository $cartRepository;
    private ProductRepository $productRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private AuthRepository $authRepository;

    public function __construct(
        CartRepository $cartRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        AuthRepository $authRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->authRepository = $authRepository;
    }

    public function getOrCreateCartForUser($user): Cart
    {
        $user = $this->authRepository->findById($user->getId());
        if (!$user) {
            $this->logger->error('User not found with ID: ' . $user->getId());
            throw new Exception('User not found');
        }

        $this->logger->info('User found with ID: ' . $user->getId());

        $cart = $this->cartRepository->findCartByUserId(['user' => $user]);
        if (!$cart) {
            $this->logger->info('Cart not found. Creating a new cart for user ID: ' . $user->getId());
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
            $this->logger->info('Cart created for user ID: ' . $user->getId());
        }

        $this->logger->info('Updating cart total after creation');
        $cart->updateTotal();
        $this->entityManager->persist($cart);

        return $cart;
    }

    public function addProductToCart($productId, int $quantity, $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized access'], 401);
        }

        $product = $this->productRepository->findById($productId);
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], 404);
        }

        $cart = $this->getOrCreateCartForUser($user);
        $this->logger->info('Cart retrieved for user ID ' . $user->getId());

        $cartProduct = $this->entityManager->getRepository(CartProduct::class)
            ->findOneBy(['cart' => $cart, 'product' => $product]);

        if ($cartProduct) {
            $this->logger->info('Updating product quantity in cart: ' . $product->getName());
            $cartProduct->setQuantity($cartProduct->getQuantity() + $quantity);
        } else {
            $this->logger->info('Adding new product to cart: ' . $product->getName());
            $cartProduct = new CartProduct();
            $cartProduct->setCart($cart);
            $cartProduct->setProduct($product);
            $cartProduct->setQuantity($quantity);
            $this->entityManager->persist($cartProduct);
        }

        $this->logger->info('Updating cart total for user ID: ' . $user->getId());
        $cart->updateTotal();
        $this->entityManager->persist($cart);
        $this->logger->info('Cart total before saving: ' . $cart->getTotal());
        $this->entityManager->flush();
        $this->logger->info('Cart total after saving: ' . $cart->getTotal());

        return new JsonResponse([
            'message' => 'Product added to cart',
            'cartTotal' => $this->getCartTotal($cart)
        ]);
    }

    public function getCartDetails($user): array
    {
        $cart = $this->getOrCreateCartForUser($user);
        $this->logger->info('Updating cart total before retrieving data.');
        $cart->updateTotal();

        $cartProducts = $cart->getCartProducts();
        $items = [];

        foreach ($cartProducts as $cartProduct) {
            $product = $cartProduct->getProduct();
            $quantity = $cartProduct->getQuantity();
            $subtotal = $product->getPrice() * $quantity;

            $items[] = [
                'id' => $product->getId(),
                'product' => $product->getName(),
                'quantity' => $quantity,
                'total' => $subtotal,
            ];
        }

        $total = $cart->getTotal();
        $this->logger->info('Cart total: ' . $total);

        return ['items' => $items, 'total' => $total];
    }

    public function getCartTotal(Cart $cart): string
    {
        return $cart->getTotal();
    }

    public function clearCart(User $user, EntityManagerInterface $em): void
    {
        $cart = $user->getCart();

        if (!$cart) {
            return;
        }

        $cartProducts = $em->getRepository(CartProduct::class)->findBy(['cart' => $cart]);

        foreach ($cartProducts as $cartProduct) {
            $em->remove($cartProduct);
        }

        $em->flush();
    }

    public function removeProductFromCart(int $productId, User $user): void
    {
        $cart = $this->cartRepository->findCartByUserId(['user' => $user]);

        if (!$cart) {
            return;
        }

        foreach ($cart->getCartProducts() as $cartProduct) {
            if ($cartProduct->getProduct()->getId() === $productId) {
                $cart->getCartProducts()->removeElement($cartProduct);
                $this->entityManager->remove($cartProduct);
                break;
            }
        }

        $cart->updateTotal();
        $this->entityManager->flush();
    }
}
