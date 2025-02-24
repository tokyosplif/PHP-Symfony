<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartProduct;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartProductRepository;
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
    private CartProductRepository $cartProductRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        CartRepository $cartRepository,
        ProductRepository $productRepository,
        CartProductRepository $cartProductRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->cartProductRepository = $cartProductRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function createCartForUser(User $user): Cart
    {
        $this->logger->info('Creating a new cart for user ID: ' . $user->getId());

        $cart = new Cart();
        $cart->setUser($user);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        $this->logger->info('Cart created for user ID: ' . $user->getId());

        return $cart;
    }

    /**
     * @throws Exception
     */
    public function addProductToCart($productId, int $quantity, $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized access'], 401);
        }

        $product = $this->getProductById($productId);
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], 404);
        }

        $cart = $this->getCartForUser($user);
        $this->logger->info('Cart retrieved for user ID ' . $user->getId());

        $this->entityManager->beginTransaction();

        try {
            $cartProduct = $this->getCartProduct($cart, $product);

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
            $this->entityManager->commit();
            $this->logger->info('Cart total after saving: ' . $cart->getTotal());

            return new JsonResponse([
                'message' => 'Product added to cart',
                'cartTotal' => $cart->getTotal()
            ]);
        } catch (Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Error adding product to cart: ' . $e->getMessage());

            return new JsonResponse(['error' => 'Failed to add product to cart'], 500);
        }
    }

    /**
     * @throws Exception
     */
    public function getCartDetails($user): array
    {
        $cart = $this->getCartForUser($user);
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
        $cart = $this->getCartForUser($user);

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

    private function getCartForUser(User $user): ?Cart
    {
        return $this->cartRepository->findCartByUserId(['user' => $user]);
    }

    private function getProductById(int $productId): ?Product
    {
        return $this->productRepository->findById($productId);
    }

    private function getCartProduct(Cart $cart, Product $product): ?CartProduct
    {
        return $this->cartProductRepository->findByCartAndProduct($cart, $product);
    }
}
