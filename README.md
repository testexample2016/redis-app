# Laravel Redis Product CRUD Project

## Overview

This project is a Laravel-based web application that demonstrates a CRUD (Create, Read, Update, Delete) interface for managing products. It uses Redis for caching product lists to improve performance and ensure data consistency.

## Features

- Product CRUD operations (Create, Read, Update, Delete)
- Redis caching for product lists
- Automatic cache invalidation and refresh on data changes
- Logging for cache and product operations
- Debug endpoints for cache inspection
- Unit and manual testing support

## Project Structure

- `app/Http/Controllers/ProductController.php` - Main controller for product logic and cache management
- `resources/views/products/` - Blade templates for product UI
- `database/migrations/` - Database schema for products and related tables
- `database/factories/ProductFactory.php` - Factory for generating test products
- `routes/web.php` - Web routes, including debug endpoints
- `test_destroy_method.php` - Script for testing product deletion and cache
- `DESTROY_METHOD_FIX.md` - Details about the destroy method and cache fix

## Setup

1. Clone the repository
2. Install dependencies:
    ```sh
    composer install
    npm install
    ```
3. Copy `.env.example` to `.env` and configure your database and Redis settings
4. Generate application key:
    ```sh
    php artisan key:generate
    ```
5. Run migrations:
    ```sh
    php artisan migrate
    ```
6. Start the development server:
    ```sh
    php artisan serve
    ```

## Usage

- Access `/products` to view, create, edit, or delete products.
- Product list is cached in Redis for performance.
- After creating, updating, or deleting a product, the cache is cleared and refreshed automatically.

## Debugging & Testing

- Use `/redis-keys` to view all Redis keys.
- Use `/debug-cache` to inspect product cache details.
- Run `php test_destroy_method.php` to test product deletion and cache behavior.
- Check logs in `storage/logs/laravel.log` for detailed operation info.

## Key Files

- [`app/Http/Controllers/ProductController.php`](app/Http/Controllers/ProductController.php)
- [`resources/views/products/index.blade.php`](resources/views/products/index.blade.php)
- [`database/migrations/2025_07_23_205451_create_products_table.php`](database/migrations/2025_07_23_205451_create_products_table.php)
- [`test_destroy_method.php`](test_destroy_method.php)
- [`DESTROY_METHOD_FIX.md`](DESTROY_METHOD_FIX.md)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

// filepath: i:\xampp8.1\htdocs\redis-app\README.md