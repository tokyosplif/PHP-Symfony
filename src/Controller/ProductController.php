<?php

namespace App\Controller;

use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    #[Route('/products', name: 'product_list', methods: ['GET'])]
    public function list(): Response
    {
        $products = $this->productService->getAllProducts();

        return $this->render('products/list.html.twig', [
            'products' => $products,
        ]);
    }
}
