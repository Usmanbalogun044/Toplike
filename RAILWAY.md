# Railway Deployment (Single Container)

This guide covers deploying the Laravel backend to Railway using a single-container image that runs Nginx + PHP-FPM via Supervisor.

## Files
- Dockerfile: .docker/railway/Dockerfile
- Nginx template: .docker/railway/nginx.conf.template (uses $PORT)
- Supervisor config: .docker/railway/supervisord.conf
- Entrypoint: .docker/scripts/entrypoint.railway.sh

## Build locally (optional)
```bash
docker build -t toplike-railway -f .docker/railway/Dockerfile .
docker run --rm -e PORT=8080 -p 8080:8080 toplike-railway
```

## Deploy on Railway
1. Create a new service in Railway and choose “Deploy from Dockerfile”.
2. Set the Dockerfile path to `.docker/railway/Dockerfile`.
3. Set environment variables:
   - `APP_ENV=production`
   - `APP_KEY=...` (generate locally via `php artisan key:generate`, then copy)
   - `JWT_SECRET=...` (generate via `php artisan jwt:secret` and copy)
   - `PORT=8080` (Railway sets `PORT` automatically; any value works)
   - Database/Redis: point to Railway add-ons (`DB_URL` for Postgres or set `DB_CONNECTION/DB_HOST/...` accordingly)
   - `MIGRATE_ON_START=true` if you want migrations to run on container start
4. Add Railway Postgres and/or Redis add-ons and connect env vars.
5. Deploy.

## Notes
- The container listens on `$PORT` and serves `public/` via Nginx.
- Composer dependencies are installed in the image and re-checked on start.
- Storage symlink and caches are created on start.
- Use `DB_URL` (supported in `config/database.php`) or classic `DB_*` variables.
- Persistent storage is limited on Railway; use managed DB/Redis for state.

## Troubleshooting
- 404: ensure `APP_URL` and `APP_ENV` are correct, and Nginx template is rendered (check logs).
- 500: run `php artisan config:clear` by temporarily overriding entrypoint or deploying a fix, then redeploy.
- DB errors: verify `DB_URL` or `DB_*` are set and that the add-on is reachable from the service.
