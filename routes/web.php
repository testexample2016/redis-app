<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Resource routes for Product CRUD operations
Route::resource('products', ProductController::class);

// Route to show all Redis keys
Route::get('/redis-keys', [ProductController::class, 'showRedisKeys']);

// Example of how you might use these routes (e.g., via Postman or a simple form)
// GET /products - Get all products (cached)
// POST /products - Create a new product (invalidates cache)
// GET /products/{id} - Get a specific product
// PUT/PATCH /products/{id} - Update a product (invalidates cache)
// DELETE /products/{id} - Delete a product (invalidates cache)
// GET /redis-keys - Show all Redis keys
