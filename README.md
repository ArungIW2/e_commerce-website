# Laravel Commerce

A modular e-commerce platform inspired by PrestaShop, rebuilt with Laravel.

## Stack
- Laravel 12 / PHP 8.2+
- Blade + Tailwind CSS
- Eloquent ORM
- SQLite for local development; MySQL/MariaDB recommended for production

## Current foundation
- Storefront homepage
- Product catalog and search
- Categories
- Product detail pages
- Basic cart route
- Product/category domain models
- Database migrations and demo seeder

## Roadmap
1. Authentication and customer accounts
2. Persistent cart and wishlist
3. Checkout, addresses, orders and order status
4. Product variants, attributes and images
5. Admin dashboard and CRUD
6. Inventory management
7. Coupons, promotions and tax rules
8. Payment/shipping abstractions
9. REST API
10. Tests, queues, caching, Docker and CI

## Local setup
```bash
composer install
cp .env.example .env
php artisan key:generate
mkdir -p database && touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Then open http://localhost:8000.
