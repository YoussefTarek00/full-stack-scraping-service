<?php

namespace Tests\Unit\Scraping;

use App\Services\Scraping\CatalogPageParser;
use PHPUnit\Framework\TestCase;

class CatalogPageParserTest extends TestCase
{
    private const PAGE_URL = 'https://books.toscrape.com/catalogue/category/books/mystery_3/index.html';

    private function fixture(): string
    {
        return file_get_contents(__DIR__.'/../../Fixtures/Scraping/catalog_page.html');
    }

    public function test_it_extracts_absolute_product_urls(): void
    {
        $parser = new CatalogPageParser;

        $urls = $parser->extractProductUrls($this->fixture(), self::PAGE_URL);

        $this->assertNotEmpty($urls);
        $this->assertContains('https://books.toscrape.com/catalogue/sharp-objects_997/index.html', $urls);

        foreach ($urls as $url) {
            $this->assertStringStartsWith('https://books.toscrape.com/catalogue/', $url);
        }
    }

    public function test_it_extracts_the_absolute_next_page_url(): void
    {
        $parser = new CatalogPageParser;

        $nextUrl = $parser->extractNextPageUrl($this->fixture(), self::PAGE_URL);

        $this->assertSame('https://books.toscrape.com/catalogue/category/books/mystery_3/page-2.html', $nextUrl);
    }
}
