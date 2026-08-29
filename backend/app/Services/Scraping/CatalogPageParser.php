<?php

namespace App\Services\Scraping;

use Symfony\Component\DomCrawler\Crawler;

class CatalogPageParser
{
    public function extractProductUrls(string $html, string $pageUrl): array
    {
        $crawler = new Crawler($html, $pageUrl);

        return $crawler->filter('article.product_pod h3 a')
            ->each(fn (Crawler $node) => $node->link()->getUri());
    }

    public function extractNextPageUrl(string $html, string $pageUrl): ?string
    {
        $crawler = new Crawler($html, $pageUrl);
        $nextLink = $crawler->filter('li.next a');

        if ($nextLink->count() === 0) {
            return null;
        }

        return $nextLink->link()->getUri();
    }
}
