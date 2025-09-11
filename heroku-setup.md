# Heroku Deployment Setup for Toplike

## Prerequisites
1. Install Heroku CLI: https://devcenter.heroku.com/articles/heroku-cli
2. Create a Heroku account: https://heroku.com

## Deployment Steps

### 1. Create Heroku App
```bash
heroku create your-app-name
```

### 2. Add Required Add-ons
```bash
# PostgreSQL database
heroku addons:create heroku-postgresql:mini

# Optional: Redis for better performance (if upgrading from database cache/sessions)
# heroku addons:create heroku-redis:mini
```

### 3. Set Environment Variables
Copy the variables from `.env.heroku` and set them:
```bash
heroku config:set APP_NAME="Toplike"
heroku config:set APP_ENV="production"
heroku config:set APP_DEBUG="false"
heroku config:set APP_KEY="$(php artisan key:generate --show)"
heroku config:set APP_URL="https://your-app-name.herokuapp.com"

# Set other variables from .env.heroku as needed
heroku config:set CLOUDINARY_URL="your-cloudinary-url"
heroku config:set PAYSTACK_PUBLIC_KEY="your-paystack-public-key"
heroku config:set PAYSTACK_SECRET_KEY="your-paystack-secret-key"

# Set mail configuration
heroku config:set MAIL_MAILER="smtp"
heroku config:set MAIL_HOST="your-mail-host"
heroku config:set MAIL_USERNAME="your-mail-username"
heroku config:set MAIL_PASSWORD="your-mail-password"
```

### 4. Deploy
```bash
git add .
git commit -m "Heroku deployment setup"
git push heroku main
```

### 5. Scale Workers (if needed)
```bash
# Scale web dyno
heroku ps:scale web=1

# Scale worker dyno for background jobs (optional, costs extra)
# heroku ps:scale worker=1
```

### 6. Run Initial Setup
```bash
# Generate application key (if not done above)
heroku run php artisan key:generate

# Run migrations (this is also done automatically in release phase)
heroku run php artisan migrate

# Clear and cache config (optional, done automatically in release phase)
heroku run php artisan config:cache
```

## File Structure
- `Procfile`: Defines web server, release phase, and worker processes
- `.env.heroku`: Template for environment variables
- `nginx.conf`: Alternative nginx configuration (not used by default)
- `.htaccess` (root): Redirects to public directory

## Important Notes
1. The `release` phase automatically runs migrations and caches config on each deploy
2. The `worker` process handles background jobs (scale separately if needed)
3. Use `heroku logs --tail` to monitor application logs
4. Use `heroku config` to view all environment variables

## Troubleshooting
- Check logs: `heroku logs --tail`
- Restart app: `heroku restart`
- Clear cache: `heroku run php artisan cache:clear`
- Run artisan commands: `heroku run php artisan command:name`