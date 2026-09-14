# Container deployment

Phase 14 provides a Docker image and a Compose configuration for deployment.

## Required environment

Create `.env` from `.env.example` and set at minimum:

- `APP_KEY`
- `APP_ENV=production`
- `APP_DEBUG=false`
- production database settings
- `MIDTRANS_SERVER_KEY` and `MIDTRANS_CLIENT_KEY`
- `BITESHIP_API_KEY` and `BITESHIP_ORIGIN_POSTAL_CODE`

Do not commit `.env` or provider secrets.

## Build and run

```bash
docker compose build
docker compose up -d
```

Run migrations explicitly during deployment:

```bash
docker compose exec app php artisan migrate --force
```

If using seeded/demo data, run the seeders separately and only in a controlled environment:

```bash
docker compose exec app php artisan db:seed --force
```

## Production notes

The bundled container uses Laravel's PHP development server for a simple single-container deployment. For higher traffic, place a production web server/reverse proxy in front of the application or replace the runtime with a dedicated PHP-FPM/FrankenPHP setup.

The GitHub Actions workflow runs the feature tests first and builds the Docker image only when tests pass. Deployment credentials and the target hosting provider should be added later as repository secrets when a concrete production platform is selected.
