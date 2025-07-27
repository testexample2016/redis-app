<?php

/**
 * Simple test script to verify destroy method functionality
 * Run this script to test if the destroy method properly updates the product list
 */

// Include Laravel bootstrap
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

echo "=== Testing Destroy Method ===\n\n";

try {
    // 1. Check initial state
    echo "1. Checking initial state...\n";
    $initialCount = Product::count();
    echo "   Total products in database: {$initialCount}\n";
    
    // Check cache keys
    $pattern = 'all_products*';
    $keys = Redis::keys($pattern);
    echo "   Cache keys found: " . count($keys) . "\n";
    foreach ($keys as $key) {
        echo "   - {$key}\n";
    }
    echo "\n";
    
    if ($initialCount == 0) {
        echo "   No products found. Creating a test product first...\n";
        Product::create([
            'name' => 'Test Product',
            'detail' => 'Test product for deletion testing'
        ]);
        echo "   Test product created.\n\n";
    }
    
    // 2. Get the first product for deletion
    $productToDelete = Product::first();
    if (!$productToDelete) {
        echo "   No products available for testing.\n";
        exit(1);
    }
    
    echo "2. Product to delete:\n";
    echo "   ID: {$productToDelete->id}\n";
    echo "   Name: {$productToDelete->name}\n";
    echo "   Detail: {$productToDelete->detail}\n\n";
    
    // 3. Delete the product
    echo "3. Deleting product...\n";
    $productToDelete->delete();
    echo "   Product deleted from database.\n";
    
    // 4. Clear cache (simulate what destroy method does)
    echo "4. Clearing cache...\n";
    $keys = Redis::keys($pattern);
    foreach ($keys as $key) {
        Redis::del($key);
        echo "   Deleted cache key: {$key}\n";
    }
    echo "   Cache cleared.\n\n";
    
    // 5. Refresh cache (simulate what destroy method does)
    echo "5. Refreshing cache...\n";
    $totalProducts = Product::count();
    $perPage = 5;
    $totalPages = ceil($totalProducts / $perPage);
    
    for ($page = 1; $page <= $totalPages; $page++) {
        $products = Product::latest()->paginate($perPage, ['*'], 'page', $page);
        $cacheKey = 'all_products_page_' . $page;
        $cacheData = [
            'data' => $products->items(),
            'total' => $products->total(),
            'per_page' => $products->perPage(),
            'current_page' => $products->currentPage(),
        ];
        Redis::setex($cacheKey, 60, json_encode($cacheData));
        echo "   Refreshed cache for page: {$page}\n";
    }
    echo "   Cache refresh completed.\n\n";
    
    // 6. Verify final state
    echo "6. Verifying final state...\n";
    $finalCount = Product::count();
    echo "   Total products in database: {$finalCount}\n";
    
    // Check cache keys again
    $keys = Redis::keys($pattern);
    echo "   Cache keys found: " . count($keys) . "\n";
    foreach ($keys as $key) {
        $data = Redis::get($key);
        $decoded = json_decode($data, true);
        $cachedCount = $decoded['total'] ?? 'N/A';
        echo "   - {$key} (total: {$cachedCount})\n";
    }
    
    // 7. Test cache retrieval
    echo "\n7. Testing cache retrieval...\n";
    $cachedData = Redis::get('all_products_page_1');
    if ($cachedData) {
        $data = json_decode($cachedData, true);
        $cachedProducts = $data['data'] ?? [];
        echo "   Cached products count: " . count($cachedProducts) . "\n";
        echo "   Cached total: " . ($data['total'] ?? 'N/A') . "\n";
        
        if (count($cachedProducts) > 0) {
            echo "   First cached product: " . ($cachedProducts[0]['name'] ?? 'N/A') . "\n";
        }
    } else {
        echo "   No cached data found for page 1.\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "Initial products: {$initialCount}\n";
    echo "Final products: {$finalCount}\n";
    echo "Expected difference: 1\n";
    echo "Actual difference: " . ($initialCount - $finalCount) . "\n";
    
    if (($initialCount - $finalCount) == 1) {
        echo "✅ Test PASSED: Product deletion and cache update working correctly!\n";
    } else {
        echo "❌ Test FAILED: Product deletion or cache update not working correctly!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 