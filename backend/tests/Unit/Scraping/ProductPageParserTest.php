<?php

namespace Tests\Unit\Scraping;

use App\Services\Scraping\ProductPageParser;
use App\Services\Scraping\ProductPageParsingException;
use PHPUnit\Framework\TestCase;

class ProductPageParserTest extends TestCase
{
    private const PAGE_URL = 'https://books.toscrape.com/catalogue/sharp-objects_997/index.html';

    private function fixture(): string
    {
        return file_get_contents(__DIR__.'/../../Fixtures/Scraping/product_page.html');
    }

    public function test_it_parses_title_price_and_image_from_a_product_page(): void
    {
        $parser = new ProductPageParser;

        $product = $parser->parse($this->fixture(), self::PAGE_URL);

        $this->assertSame('Sharp Objects', $product->title);
        $this->assertSame(47.82, $product->price);
        $this->assertSame(
            'https://books.toscrape.com/media/cache/c0/59/c05972805aa7201171b8fc71a5b00292.jpg',
            $product->imageUrl,
        );
    }

    public function test_it_throws_when_the_page_does_not_look_like_a_product_page(): void
    {
        $parser = new ProductPageParser;

        $this->expectException(ProductPageParsingException::class);

        $parser->parse('<html><body>Not a product page</body></html>', self::PAGE_URL);
    }
}
