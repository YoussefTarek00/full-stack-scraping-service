# frontend

Next.js frontend for the scraping service. Renders the `/products` page,
which fetches from the Laravel API and refreshes every 30 seconds.

## Requirements

Nothing needs to be installed locally beyond Docker — see the root
`README.md` for the full `docker compose` setup once it's wired up.

## Configuration

`NEXT_PUBLIC_API_URL` (see `.env.example`) points at the Laravel backend's
base URL. Defaults to `http://localhost:8000`.

## Local commands

```bash
npm install
npm run dev
npm run build
npm run lint
```
