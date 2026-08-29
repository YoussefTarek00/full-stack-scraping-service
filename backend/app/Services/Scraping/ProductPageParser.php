<?php

namespace App\Services\Scraping;

use Symfony\Component\DomCrawler\Crawler;

class ProductPageParser
{
    public function parse(string $html, string $pageUrl): ScrapedProduct
    {
        $crawler = new Crawler($html, $pageUrl);

        $title = $crawler->filter('div.product_main h1');
        $price = $crawler->filter('div.product_main p.price_color');
        $image = $crawler->filter('div.thumbnail img');

        if ($title->count() === 0 || $price->count() === 0 || $image->count() === 0) {
            throw new ProductPageParsingException("Could not locate title, price, or image on {$pageUrl}");
        }

        return new ScrapedProduct(
            title: trim($title->text()),
            price: $this->parsePrice($price->text()),
            imageUrl: $image->image()->getUri(),
        );
    }

    private function parsePrice(string $rawPrice): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $rawPrice);
    }
}
