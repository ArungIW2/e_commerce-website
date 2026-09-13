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
- Live multi-courier shipping rates through Biteship
- Shipping courier/service and shipping cost snapshots on orders
- Shipping fallback methods retained for backwards compatibility
- Payment status and coupons
- Product attributes, values and variants with SKU/stock/price overrides
- Admin dashboard and catalog/inventory management
- Product image gallery with upload, primary-image selection and deletion
- Customer wishlist with duplicate protection, removal and add-to-cart flow
- Midtrans Snap payment integration with webhook signature verification
- Automated feature tests and GitHub Actions CI

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

## Biteship configuration
Create a Biteship API key and configure the seller/origin postal code in `.env`:

```env
BITESHIP_API_KEY=
BITESHIP_BASE_URL=https://api.biteship.com
BITESHIP_ORIGIN_POSTAL_CODE=
BITESHIP_COURIERS=jne,jnt,sicepat,anteraja
BITESHIP_DEFAULT_ITEM_WEIGHT_GRAMS=1000
BITESHIP_TIMEOUT=10
```

The checkout requests rates from Biteship using the destination postal code and cart contents. The selected courier/service is re-quoted server-side before the order is created, so the browser cannot set an arbitrary shipping price. Product weight is currently represented by the configurable default weight per cart item; adding per-product/package dimensions is planned for a later shipping hardening phase.

For development, use a Biteship Testing/Sandbox API key and never commit the key. Rates API requests may incur provider charges even in testing mode, according to Biteship's current testing policy.

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
3. ~~Courier/shipping API integration~~
4. ~~Automated testing and Midtrans hardening~~
5. Shipping order creation, pickup, waybill and webhook tracking
6. Reporting and analytics dashboard
7. REST API
8. Docker + CI/CD
