<?php

namespace App\Repositories;

use App\Models\Product;
use App\Services\Scraping\ScrapedProduct;

class ProductRepository
{
    public function store(ScrapedProduct $product): Product
    {
        return Product::create([
            'title' => $product->title,
            'price' => $product->price,
            'image_url' => $product->imageUrl,
        ]);
    }
}
