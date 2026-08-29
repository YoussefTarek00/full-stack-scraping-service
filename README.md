# full-stack-scraping-service

A small web scraping service: a Laravel API backend that scrapes product
listings and serves them over HTTP, a Go microservice that manages proxy
rotation for the scraper, and a Next.js frontend that displays the results
in a live-updating grid.

## Architecture

- **Scraping**: `backend` fetches pages from `books.toscrape.com`, using a
  rotated `User-Agent` and a proxy obtained from `proxy-service` on each
  request, then parses and stores the results in MySQL.
- **Proxy rotation**: `backend` calls `proxy-service` (`GET /proxy/next`)
  before each scrape request and reports the outcome back
  (`POST /proxy/report`), so failing proxies get excluded temporarily.
- **API**: `backend` exposes `GET /api/products`, reading from MySQL.
- **Frontend**: `frontend`'s `/products` page fetches from that API on
  load and every 30 seconds after.

## Components

- **backend/** — Laravel 13 API and scraper. See `backend/README.md`.
- **proxy-service/** — Go microservice managing a pool of proxy endpoints,
  handing them out round-robin and tracking which ones are failing. See
  `proxy-service/README.md`.
- **frontend/** — Next.js app rendering `/products`. See `frontend/README.md`.

## Why books.toscrape.com, not Amazon/Jumia

Amazon and Jumia actively block scrapers (CAPTCHAs, bot detection) and
scraping them raises ToS concerns for a demo. books.toscrape.com is built
specifically for scraping practice — same Guzzle + UA-rotation + HTML
parsing pattern, without the anti-bot fight or legal ambiguity.

## Why the proxy service works this way

There's no budget for real paid rotating proxies in a trial task, so
`proxy-service` implements the actual proxy-management architecture
(a pool, round-robin rotation, health tracking via reported outcomes)
over a pool of proxy endpoints supplied through configuration — it's
built to be pointed at real proxies by changing one env var, without
code changes.

The Laravel backend treats the proxy service as a **hard dependency**:
if it's unreachable or has no healthy proxy, scraping fails with a clear
error rather than silently scraping directly. `SCRAPER_ALLOW_DIRECT_CONNECTION`
is an explicit opt-in for exactly this trial task's situation (no real
proxies configured) — see `backend/README.md` for details.

## Running everything

Requires Docker and Docker Compose. No other local toolchain needed.

```bash
cp backend/.env.example backend/.env
cp proxy-service/.env.example proxy-service/.env

docker compose up -d --build
docker compose exec backend php artisan scrape:products --limit=20
```

Then open:

- Frontend: http://localhost:3001/products
- API: http://localhost:8000/api/products
- Proxy service health check: http://localhost:8081/health

`docker compose up` runs the Laravel migrations and generates an `APP_KEY`
automatically on first boot — no manual setup step needed beyond copying
the two env files above.

To scrape again later (the schema has no de-duplication key, so re-running
inserts additional rows — see `backend/README.md`):

```bash
docker compose exec backend php artisan scrape:products --limit=20
```

To run each service's test suite:

```bash
docker build --target=test -t backend-test ./backend && docker run --rm backend-test
docker run --rm -v "$PWD/proxy-service":/app -w /app golang:1.22-alpine go test ./...
cd frontend && npm run lint && npx tsc --noEmit
```

## Repository layout

```
backend/              Laravel API + scraper
frontend/             Next.js UI
proxy-service/        Go proxy-rotation microservice
docker-compose.yml    Orchestrates all four services (+ MySQL)
```
