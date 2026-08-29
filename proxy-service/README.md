# proxy-service

A small Go microservice that manages a pool of proxy endpoints and rotates
through them, so the Laravel scraper never talks to the target site through
the same proxy on consecutive requests. It does not proxy traffic itself —
it hands out _which_ proxy to use next, and tracks which ones are currently
misbehaving.

## API

| Method | Path | Description |
| GET | `/proxy/next` | Returns the next healthy proxy in round-robin order: `{"proxy": "..."}`. `503` if none are configured or all are in cooldown. |
| POST | `/proxy/report` | Reports the outcome of using a proxy: `{"proxy": "...", "success": false}`. A failed report puts the proxy in cooldown for `PROXY_COOLDOWN_SECONDS`; a successful report clears it. `404` if the proxy isn't managed by this pool. |
| GET | `/health` | Liveness check. |

## Configuration

See `.env.example`:

- `PORT` — port to listen on (default `8081`).
- `PROXY_LIST` — comma-separated proxy URLs.
- `PROXY_COOLDOWN_SECONDS` — cooldown period after a failed report (default `60`).

## Running locally

```bash
docker build -t proxy-service .
docker run --rm -p 8081:8081 --env-file .env proxy-service
```

## Testing

```bash
go test ./...
```
