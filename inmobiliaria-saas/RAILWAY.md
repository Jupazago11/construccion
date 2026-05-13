# Railway Deploy

## Service settings

- Recommended Root Directory: `inmobiliaria-saas`
- Recommended Config file path: `/inmobiliaria-saas/railway.json`
- Builder: Railpack
- Public domain: generate it only for the app service

If Railway still builds from the repository root, the root `Dockerfile` and root
`railway.json` are prepared as a fallback and will build the Laravel app from the
`inmobiliaria-saas` directory.

## Required variables

Set these in the Railway app service:

```env
APP_NAME="Inmobiliaria SaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-railway-domain.up.railway.app
APP_KEY=base64:...
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

SUPERADMIN_USERNAME=superadmin
SUPERADMIN_NAME="Super Admin"
SUPERADMIN_EMAIL=admin@example.com
SUPERADMIN_PASSWORD=change-this-password
# Optional, only when you intentionally want deploys to reset the password:
# SUPERADMIN_RESET_PASSWORD=true

DB_CONNECTION=pgsql
DATABASE_URL=${{Postgres.DATABASE_URL}}
DB_URL=${{Postgres.DATABASE_URL}}
DB_SSLMODE=require

LOG_CHANNEL=stderr
LOG_LEVEL=info

CACHE_STORE=database
SESSION_DRIVER=database
SESSION_ENCRYPT=false
QUEUE_CONNECTION=database

FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_DEFAULT_REGION=auto
R2_BUCKET=...
R2_ENDPOINT=...
R2_ROOT_PREFIX=inmobiliaria-saas
R2_USE_PATH_STYLE_ENDPOINT=true
```

Use the same variables for optional worker or cron services.

If the login page looks unstyled, inspect the page source. CSS and JS URLs must
start with `https://`. If they start with `http://`, confirm `APP_URL` uses the
Railway HTTPS domain and that Laravel trusts Railway proxy headers.

## Production seed

`railway/init-app.sh` runs:

```sh
php artisan migrate --force
php artisan db:seed --force
```

`DatabaseSeeder` is intentionally minimal for production. It only creates or
updates roles/permissions and the SuperAdmin user. Demo seeders remain in the
repository, but they are not called by the default seeder.

## Optional services

For a queue worker service, use this start command:

```sh
chmod +x ./railway/run-worker.sh && sh ./railway/run-worker.sh
```

For a scheduler service, use this start command:

```sh
chmod +x ./railway/run-cron.sh && sh ./railway/run-cron.sh
```
