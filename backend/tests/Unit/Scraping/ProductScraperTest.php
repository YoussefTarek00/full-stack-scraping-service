<?php

namespace Tests\Unit\Scraping;

use App\Services\Scraping\CatalogPageParser;
use App\Services\Scraping\ProductPageParser;
use App\Services\Scraping\ProductScraper;
use App\Services\Scraping\ProxyGateway;
use App\Services\Scraping\UserAgentRotator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ProductScraperTest extends TestCase
{
    private const CATALOG_URL = 'https://books.toscrape.com/catalogue/category/books/mystery_3/index.html';

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__."/../../Fixtures/Scraping/{$name}");
    }

    public function test_it_scrapes_up_to_the_requested_number_of_products_using_a_rotated_proxy_and_user_agent(): void
    {
        $siteMock = new MockHandler([
            new Response(200, [], $this->fixture('catalog_page.html')),
            new Response(200, [], $this->fixture('product_page.html')),
            new Response(200, [], $this->fixture('product_page.html')),
        ]);
        $siteHistory = [];
        $siteStack = HandlerStack::create($siteMock);
        $siteStack->push(Middleware::history($siteHistory));
        $siteClient = new Client(['handler' => $siteStack]);

        $proxyMock = new MockHandler([
            new Response(200, [], json_encode(['proxy' => 'http://proxy-1:8000'])),
            new Response(204),
            new Response(200, [], json_encode(['proxy' => 'http://proxy-2:8000'])),
            new Response(204),
            new Response(200, [], json_encode(['proxy' => 'http://proxy-3:8000'])),
            new Response(204),
        ]);
        $proxyClient = new Client(['handler' => HandlerStack::create($proxyMock)]);
        $proxyGateway = new ProxyGateway($proxyClient, new NullLogger, allowDirectConnection: false);

        $userAgents = new UserAgentRotator(['ua-a', 'ua-b']);

        $scraper = new ProductScraper(
            $siteClient,
            $userAgents,
            $proxyGateway,
            new CatalogPageParser,
            new ProductPageParser,
        );

        $products = iterator_to_array($scraper->scrape(self::CATALOG_URL, maxProducts: 2));

        $this->assertCount(2, $products);
        $this->assertSame('Sharp Objects', $products[0]->title);
        $this->assertSame(47.82, $products[0]->price);

        $this->assertCount(3, $siteHistory);
        $this->assertSame('http://proxy-1:8000', $siteHistory[0]['options']['proxy']);
        $this->assertSame('http://proxy-2:8000', $siteHistory[1]['options']['proxy']);
        $this->assertSame('http://proxy-3:8000', $siteHistory[2]['options']['proxy']);
        $this->assertSame('ua-a', $siteHistory[0]['request']->getHeaderLine('User-Agent'));
        $this->assertSame('ua-b', $siteHistory[1]['request']->getHeaderLine('User-Agent'));
        $this->assertSame('ua-a', $siteHistory[2]['request']->getHeaderLine('User-Agent'));
    }
}
