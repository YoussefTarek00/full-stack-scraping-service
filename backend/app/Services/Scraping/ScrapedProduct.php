<?php

namespace App\Services\Scraping;

class ScrapedProduct
{
    public function __construct(
        public readonly string $title,
        public readonly float $price,
        public readonly string $imageUrl,
    ) {}
}
