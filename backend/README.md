# backend

Laravel API backend for the scraping service. Owns the `Product` data and,
in later phases, the scraper that populates it and the `/api/products`
endpoint the frontend consumes.

## Requirements

Nothing needs to be installed locally beyond Docker — see the root
`README.md` for the full `docker compose` setup once it's wired up.

## Data model

`products`: `id`, `title`, `price`, `image_url`, `created_at`. No
`updated_at` — rows are immutable once scraped.

## Local commands

```bash
docker build -t backend .
docker run --rm --env-file .env backend php artisan migrate --force
docker run --rm --env-file .env backend php artisan test
```
