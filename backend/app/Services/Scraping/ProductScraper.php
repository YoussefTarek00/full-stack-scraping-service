<?php

namespace App\Services\Scraping;

use Generator;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class ProductScraper
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly UserAgentRotator $userAgents,
        private readonly ProxyGateway $proxyGateway,
        private readonly CatalogPageParser $catalogParser,
        private readonly ProductPageParser $productParser,
    ) {}

    public function scrape(string $listingUrl, int $maxProducts): Generator
    {
        $scraped = 0;
        $pageUrl = $listingUrl;

        while ($pageUrl !== null && $scraped < $maxProducts) {
            $pageHtml = $this->fetch($pageUrl);
            $productUrls = $this->catalogParser->extractProductUrls($pageHtml, $pageUrl);

            foreach ($productUrls as $productUrl) {
                if ($scraped >= $maxProducts) {
                    break;
                }

                $productHtml = $this->fetch($productUrl);
                yield $this->productParser->parse($productHtml, $productUrl);
                $scraped++;
            }

            $pageUrl = $this->catalogParser->extractNextPageUrl($pageHtml, $pageUrl);
        }
    }

    private function fetch(string $url): string
    {
        $proxy = $this->proxyGateway->next();

        $options = [
            'headers' => ['User-Agent' => $this->userAgents->next()],
        ];

        if ($proxy !== null) {
            $options['proxy'] = $proxy;
        }

        try {
            $response = $this->client->request('GET', $url, $options);
        } catch (GuzzleException $e) {
            if ($proxy !== null) {
                $this->proxyGateway->report($proxy, success: false);
            }

            throw $e;
        }

        if ($proxy !== null) {
            $this->proxyGateway->report($proxy, success: true);
        }

        return (string) $response->getBody();
    }
}
