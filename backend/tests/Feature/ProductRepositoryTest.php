<?php

namespace Tests\Feature;

use App\Repositories\ProductRepository;
use App\Services\Scraping\ScrapedProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_scraped_product(): void
    {
        $repository = new ProductRepository;

        $repository->store(new ScrapedProduct(
            title: 'Sharp Objects',
            price: 47.82,
            imageUrl: 'https://books.toscrape.com/media/cache/c0/59/c05972805aa7201171b8fc71a5b00292.jpg',
        ));

        $this->assertDatabaseHas('products', [
            'title' => 'Sharp Objects',
            'price' => '47.82',
            'image_url' => 'https://books.toscrape.com/media/cache/c0/59/c05972805aa7201171b8fc71a5b00292.jpg',
        ]);
    }
}
