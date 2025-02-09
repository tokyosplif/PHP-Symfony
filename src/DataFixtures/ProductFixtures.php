<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            ['name' => 'Laptop', 'price' => 1000],
            ['name' => 'Smartphone', 'price' => 500],
            ['name' => 'Headphones', 'price' => 150],
            ['name' => 'Keyboard', 'price' => 50],
            ['name' => 'Monitor', 'price' => 200],
        ];

        foreach ($products as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);

            $manager->persist($product);
        }
        $manager->flush();
    }
}