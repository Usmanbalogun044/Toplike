# Railway Deployment (Single Container)

This guide covers deploying the Laravel backend to Railway using a single-container image that runs Nginx + PHP-FPM via Supervisor.

## Files

-   Dockerfile: `docker/production/Dockerfile`
-   Nginx template: `docker/production/nginx.conf.template`
-   Supervisor config: `docker/production/supervisord.conf`
-   Entrypoint: `docker/production/entrypoint.sh`

## Deploy on Railway

1. Create a new service in Railway and choose "GitHub Repo".
2. Select your repository.
3. Go to "Settings" -> "Build" -> "Dockerfile Path" and set it to:
   `docker/production/Dockerfile`
4. Go to "Settings" -> "Build" -> "Root Directory" and set it to: `/` (default).
5. Go to "Variables" and add:
    - `APP_ENV=production`
    - `APP_KEY` (Generate one locally with `php artisan key:generate --show` and paste it here)
    - `APP_URL` (Your Railway domain, e.g., `https://web-production-xxxx.up.railway.app`)
    - `DB_CONNECTION=pgsql`
    - `MIGRATE_ON_START=true`
6. Add a PostgreSQL database service in Railway.
    - Railway will automatically provide `DATABASE_URL` (or `DB_URL` logic depending on your app's config).
    - **Important**: Ensure your `config/database.php` uses `DB_URL` if Railway provides it, or manually map the variables (`DB_HOST`, `DB_PORT`, etc.) from the Postgres service service variables to your app service variables.
7. (Optional) Add a Redis service for Cache/Session/Queue.
    - Map `REDIS_HOST`, `REDIS_PASSWORD`, etc.

## Notes

-   The container listens on `$PORT` (Railway provides this automatically).
-   The Dockerfile matches the Render setup, ensuring consistency across platforms.
-   Assets are compiled during the build (Node.js stage).
-   Migrations run on start if `MIGRATE_ON_START=true`.
