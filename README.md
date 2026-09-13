# Laravel Commerce

A modular e-commerce platform inspired by PrestaShop, rebuilt with Laravel.

## Stack
- Laravel 12 / PHP 8.2+
- Blade + Tailwind CSS
- Eloquent ORM
- SQLite for local development; MySQL/MariaDB recommended for production

## Current features
- Storefront homepage, catalog, categories, search and product detail
- Customer authentication and account area
- Persistent cart with guest-cart merge
- Checkout, addresses, orders, cancellation and fulfillment status
- Shipping methods, payment status and coupons
- Product attributes, values and variants with SKU/stock/price overrides
- Admin dashboard and catalog/inventory management
- Product image gallery with upload, primary-image selection and deletion
- Customer wishlist with duplicate protection, removal and add-to-cart flow
- Midtrans Snap payment integration with webhook signature verification

## Product image storage
Uploaded product images use Laravel's `public` filesystem disk. After installing the project, create the public storage symlink:

```bash
php artisan storage:link
```

The image manager accepts JPEG, PNG and WebP files up to 4 MB.

## Midtrans configuration
Create/configure a Midtrans merchant account, then copy the server and client keys into `.env`:

```env
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

Use `false` for Sandbox during development. Configure the Midtrans HTTP notification URL to your deployed application's `/payments/midtrans/notification` endpoint. Never commit the server key.

## Local setup
```bash
composer install
cp .env.example .env
php artisan key:generate
mkdir -p database && touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Then open http://localhost:8000.

## Development admin
The seeded development account is `admin@example.com` with password `password`. Change or remove these credentials before production deployment.

## Roadmap
1. ~~Wishlist~~
2. ~~Real payment gateway integration~~
3. Courier/shipping API integration
4. Automated feature/unit tests and security hardening
5. Reporting and analytics dashboard
6. REST API
7. Docker + CI/CD
