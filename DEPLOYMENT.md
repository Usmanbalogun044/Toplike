# Deployment (Docker)

This doc covers deploying the Laravel backend using the production Docker stack.

## Prerequisites
- Docker (server or VM) with ports 80 available (443 if you add TLS).
- A DNS pointing to your server if using a domain.
- A secure place to store secrets (e.g. `.env.production`).
 - Do not commit secrets to the repo. Use environment files or a secret manager.

## Prepare environment
1. Copy `.env.production.example` to `.env.production` and fill values.
2. Ensure `APP_KEY` and `JWT_SECRET` are set (generate locally via `php artisan` or create random strings).

## Build & run
```bash
# Build images
docker compose -f docker-compose.prod.yml build
# Start services
docker compose -f docker-compose.prod.yml up -d
# Check logs
docker compose -f docker-compose.prod.yml logs -f app web
```

## Post-deploy tasks
```bash
# Migrations (set MIGRATE_ON_START=true to auto-run)
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
# Cache warmup
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
```

## Optional TLS
- Use a reverse proxy (Traefik, Caddy, or Nginx with certs) to terminate TLS.
- If you need Nginx to serve TLS directly, mount certs and configure a 443 server block.
 - Consider Cloudflare or an edge proxy for DDoS protection and caching.

## Backups & data
- MySQL data persisted in `db_data` volume.
- App storage persisted in `storage_data` and `bootstrap_cache` volumes.
 - Regularly export DB dumps and storage snapshots.

## Scaling & monitoring
- Scale queue workers: `docker compose -f docker-compose.prod.yml up -d --scale queue=3`.
- Consider Laravel Horizon for queue monitoring if needed.
 - Add centralized logs (ELK/CloudWatch) and metrics (Prometheus) for enterprise readiness.

## Rollbacks
- Revert to previous image by re-building with a prior tag or commit.
- Keep migrations backward compatible where possible.
 - Use feature flags for risky changes.
