# Docker Dev Setup

- Prereqs: Docker Desktop (Windows), ports 80/3306/6379/8025 available.
- Services: app (PHP-FPM), web (Nginx), db (MySQL 8), redis, queue, mailhog.

## Quick Start

1. Copy or edit environment
   - Edit .env.docker as needed (DB credentials, mail, etc.).

2. Build and start

```powershell
# From repo root
docker compose up -d --build
# Tail app logs
docker compose logs -f app
```

3. First run tasks (inside app)

```powershell
# Optional helpers
docker compose exec app composer install
docker compose exec app php artisan migrate --force
docker compose exec app php artisan jwt:secret --force
docker compose exec app php artisan storage:link
```

4. Open the app
- API/UI: http://localhost
- Mailhog: http://localhost:8025 (SMTP on 1025)

## Common Commands

```powershell
# Artisan, tinker, tests
docker compose exec app php artisan list
docker compose exec app php artisan test

# Queue worker logs
docker compose logs -f queue

# Stop and clean
docker compose down -v
```

## Notes
- DB defaults: host db, user toplike, pass toplike, database toplike.
- Redis host: redis, port 6379.
- If you change .env.docker, restart with `docker compose up -d`.
- Windows file permissions are handled via www-data UID/GID 1000.
 - Healthchecks ensure `db`, `redis`, `web`, and `app` are up before dependencies start.
 - EntryPoint waits for DB and runs migrations/key generation on first start.
