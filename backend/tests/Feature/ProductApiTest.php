<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_stored_products_as_json(): void
    {
        $older = Product::create(['title' => 'Older Book', 'price' => 10.50, 'image_url' => 'https://example.com/older.jpg']);
        $older->forceFill(['created_at' => now()->subMinute()])->save();

        $newer = Product::create(['title' => 'Newer Book', 'price' => 20.00, 'image_url' => 'https://example.com/newer.jpg']);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $newer->id);
        $response->assertJsonPath('data.0.title', 'Newer Book');
        $response->assertJsonPath('data.1.id', $older->id);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'price', 'image_url', 'created_at'],
            ],
        ]);
    }

    public function test_it_returns_an_empty_list_when_there_are_no_products(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertExactJson(['data' => []]);
    }
}
