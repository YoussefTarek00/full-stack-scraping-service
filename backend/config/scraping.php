<?php

return [
    'proxy_service' => [
        'base_uri' => env('PROXY_SERVICE_URL', 'http://proxy-service:8081'),
        'timeout' => (float) env('PROXY_SERVICE_TIMEOUT', 5),
    ],

    'allow_direct_connection' => (bool) env('SCRAPER_ALLOW_DIRECT_CONNECTION', false),

    'request_timeout' => (float) env('SCRAPER_REQUEST_TIMEOUT', 10),

    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
    ],

    'target' => [
        'listing_url' => env('SCRAPER_TARGET_URL', 'https://books.toscrape.com/catalogue/page-1.html'),
        'max_products' => (int) env('SCRAPER_MAX_PRODUCTS', 20),
    ],
];
