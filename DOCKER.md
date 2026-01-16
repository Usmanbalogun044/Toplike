# Docker Dev Setup (Simple MVP)

- Prereqs: Docker Desktop (Windows), ports 8000/3306 available.
- Services: app (single PHP container using `php artisan serve`) and `db` (MySQL 8).

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
- App: http://localhost:8000

## Common Commands

```powershell
# Artisan, tinker, tests
docker compose exec app php artisan list
docker compose exec app php artisan test

# Stop and clean
docker compose down -v
```

## Notes
- DB defaults: host `db`, user `toplike`, pass `toplike`, database `toplike`.
- If you change `.env.docker`, restart with `docker compose up -d`.
- Queue is `sync` and sessions/cache use `file` to keep the stack minimal.
- Entry point waits for DB, installs deps, runs migrations, and starts the server on port 8000.
