# backend

Laravel API backend for the scraping service. Owns the `Product` data, the
scraper that populates it, and the `/api/products` endpoint the frontend
consumes.

## Requirements

Nothing needs to be installed locally beyond Docker — see the root
`README.md` for the full `docker compose` setup once it's wired up.

## Data model

`products`: `id`, `title`, `price`, `image_url`, `created_at`. No
`updated_at` — rows are immutable once scraped.

## Scraping

`php artisan scrape:products {url?} {--limit=}` crawls a books.toscrape.com
catalogue listing page, follows pagination, fetches each product's own page,
and stores the results. Each request rotates its `User-Agent` header
(`app/Services/Scraping/UserAgentRotator.php`) and is routed through a proxy
obtained from the Go `proxy-service` (`app/Services/Scraping/ProxyGateway.php`).

The proxy service is a hard dependency: if it's unreachable or has no
healthy proxy to hand out, the command fails with a clear error rather than
silently scraping directly. Set `SCRAPER_ALLOW_DIRECT_CONNECTION=true` to
explicitly opt into a direct connection when the proxy service is
unavailable — useful for local development without `proxy-service` running.

Relevant env vars (see `.env.example`): `PROXY_SERVICE_URL`,
`PROXY_SERVICE_TIMEOUT`, `SCRAPER_ALLOW_DIRECT_CONNECTION`,
`SCRAPER_REQUEST_TIMEOUT`, `SCRAPER_TARGET_URL`, `SCRAPER_MAX_PRODUCTS`.

## API

`GET /api/products` returns stored products, newest first:

```json
{"data": [{"id": 1, "title": "...", "price": "19.99", "image_url": "...", "created_at": "..."}]}
```

## Local commands

```bash
docker build -t backend .
docker run --rm --env-file .env backend php artisan migrate --force
docker run --rm --env-file .env backend php artisan test
docker run --rm --env-file .env backend php artisan scrape:products --limit=10
```
