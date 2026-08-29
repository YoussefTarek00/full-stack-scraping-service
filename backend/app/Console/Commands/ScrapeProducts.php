<?php

namespace App\Console\Commands;

use App\Repositories\ProductRepository;
use App\Services\Scraping\ProductScraper;
use App\Services\Scraping\ProxyUnavailableException;
use Illuminate\Console\Command;

class ScrapeProducts extends Command
{
    protected $signature = 'scrape:products {url?} {--limit=}';

    protected $description = 'Scrape products from the configured target site and store them in the database';

    public function handle(ProductScraper $scraper, ProductRepository $repository): int
    {
        $listingUrl = $this->argument('url') ?? config('scraping.target.listing_url');
        $limit = (int) ($this->option('limit') ?? config('scraping.target.max_products'));

        $this->info("Scraping up to {$limit} products from {$listingUrl}");

        $stored = 0;

        try {
            foreach ($scraper->scrape($listingUrl, $limit) as $product) {
                $repository->store($product);
                $stored++;
                $this->line("Stored: {$product->title}");
            }
        } catch (ProxyUnavailableException $e) {
            $this->error("Proxy service unavailable: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Done. Stored {$stored} product(s).");

        return self::SUCCESS;
    }
}
