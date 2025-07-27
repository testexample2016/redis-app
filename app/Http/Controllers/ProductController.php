<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis; // Import Redis Facade
use Illuminate\Support\Facades\Log; // Import Log Facade


class ProductController extends Controller
{
     // Define the cache key for all products
    const ALL_PRODUCTS_CACHE_KEY = 'all_products';
    // Define the cache expiration time in seconds (e.g., 1 hour)
    const CACHE_EXPIRATION_SECONDS = 60; // 60
     // Define items per page
    const ITEMS_PER_PAGE = 5;

     /* Display a listing of the resource.
     * Fetches products from Redis cache, if not found, retrieves from DB and caches it.
     */
    public function index()
    {
        try {
            $perPage = self::ITEMS_PER_PAGE;
            $page = request()->input('page', 1);
            $cacheKey = self::ALL_PRODUCTS_CACHE_KEY . "_page_" . $page;
            $cachedProducts = Redis::get($cacheKey);

            if ($cachedProducts) {
                $data = json_decode($cachedProducts, true);
                // Convert each array to an object for Blade compatibility
                $items = array_map(function($item) {
                    return (object) $item;
                }, $data['data']);
                $products = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $data['total'],
                    $data['per_page'],
                    $data['current_page'],
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                Log::info("Products loaded from cache for page: {$page}");
            } else {
                $products = Product::latest()->paginate($perPage);
                $cacheData = [
                    'data' => $products->items(),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                ];
                Redis::setex($cacheKey, self::CACHE_EXPIRATION_SECONDS, json_encode($cacheData));
                Log::info("Products loaded from database and cached for page: {$page}");
            }

        //     return response()->json([
        //     'message' => 'Product retrieved successfully.',
        //     'products' => $products
        // ]);

            return view('products.index', compact('products'))
                ->with('i', (request()->input('page', 1) - 1) * 5);
        } catch (\Exception $e) {
            Log::error("Error in index method: " . $e->getMessage());
            // Fallback to database if cache fails
            $products = Product::latest()->paginate(5);
            return view('products.index', compact('products'))
                ->with('i', (request()->input('page', 1) - 1) * 5);
        }

 
    }
/**
 * Show the form for creating a new resource.
 */
public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     * Invalidates the 'all_products' cache after creation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'detail' => 'nullable|string',
        ]);

        $product = Product::create($request->all());

        // Clear and refresh the cache
        $this->clearAllProductCache();
        $this->refreshProductCache();

        Log::info("Product created: ID {$product->id}, Name: {$product->name}");

        return redirect()->route('products.index')
                        ->with('success','Product created successfully.');
    }

    /**
     * Display the specified resource.
     * Can be extended to cache individual products if needed, but for simplicity, directly fetches.
     */
    public function show(Product $product)
    {
        // return response()->json([
        //     'message' => 'Product retrieved successfully.',
        //     'product' => $product
        // ]);

        return view('products.show',compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('products.edit',compact('product'));
    }

    /**
     * Update the specified resource in storage.
     * Invalidates the 'all_products' cache after update.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'detail' => 'nullable|string',
        ]);

        $product->update($request->all());

        // Clear and refresh the cache
        $this->clearAllProductCache();
        $this->refreshProductCache();

        Log::info("Product updated: ID {$product->id}, Name: {$product->name}");

        return redirect()->route('products.index')
                        ->with('success','Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     * Invalidates the 'all_products' cache after deletion.
     */
    public function destroy(Product $product)
    {
        // Store product info for logging
        $productId = $product->id;
        $productName = $product->name;

        // Delete the product from database
        $product->delete();

        // Clear all product cache
        $this->clearAllProductCache();

        // Force refresh the cache by getting fresh data
        // This ensures the next request will get updated data
        $this->refreshProductCache();

        Log::info("Product deleted: ID {$productId}, Name: {$productName}");

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully');
    }

    private function clearAllProductCache()
    {
        try {
            $pattern = self::ALL_PRODUCTS_CACHE_KEY . '*';
            $keys = Redis::keys($pattern);
            Log::info('Found Redis keys to delete: ', $keys);

            if (!empty($keys)) {
                foreach ($keys as $key) {
                    Redis::del($key);
                    Log::info('Deleted Redis key: ' . $key);
                }
                Log::info('Cache clearing completed successfully');
            } else {
                Log::info('No cache keys found to delete');
            }
        } catch (\Exception $e) {
            Log::error("Error clearing product cache: " . $e->getMessage());
        }
    }

    private function refreshProductCache()
    {
          try {
        $perPage = self::ITEMS_PER_PAGE;
        $products = Product::latest()->paginate($perPage);
        $totalPages = $products->lastPage();

        for ($page = 1; $page <= $totalPages; $page++) {
            $pageProducts = Product::latest()->paginate($perPage, ['*'], 'page', $page);
            $cacheKey = self::ALL_PRODUCTS_CACHE_KEY . "_page_" . $page;
            $cacheData = [
                'data' => $pageProducts->items(),
                'total' => $pageProducts->total(),
                'per_page' => $pageProducts->perPage(),
                'current_page' => $pageProducts->currentPage(),
            ];
            Redis::setex($cacheKey, self::CACHE_EXPIRATION_SECONDS, json_encode($cacheData));
        }

        Log::info("Product cache refreshed for {$totalPages} pages.");
    } catch (\Exception $e) {
        Log::error("Error refreshing product cache: " . $e->getMessage());
    }
    }

    /**
     * Display all Redis keys.
     * This is primarily for debugging/demonstration purposes.
     */
    public function showRedisKeys()
    {
        try {
            // Get all keys using the KEYS command.
            // WARNING: KEYS * can be slow on large production datasets.
            // Use SCAN command for production environments.
            $keys = Redis::keys('*');

            return response()->json([
                'message' => 'Redis keys retrieved successfully.',
                'keys' => $keys
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving Redis keys.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug method to check cache status for products.
     * This helps in troubleshooting cache issues.
     */
    public function debugCache()
    {
        try {
            $pattern = self::ALL_PRODUCTS_CACHE_KEY . '*';
            $keys = Redis::keys($pattern);
            $cacheData = [];
            
            foreach ($keys as $key) {
                $data = Redis::get($key);
                $cacheData[$key] = $data ? json_decode($data, true) : null;
            }
            
            $totalProducts = Product::count();
            
            return response()->json([
                'message' => 'Cache debug information',
                'total_products_in_db' => $totalProducts,
                'cache_keys_found' => $keys,
                'cache_data' => $cacheData,
                'cache_pattern' => $pattern
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error debugging cache.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
{
    $query = $request->input('q');
    if (!$query) {
        return redirect()->route('products.index')->with('error', 'Search query cannot be empty.');
    }

    // Search products by name or detail
    $products = Product::where('name', 'like', "%{$query}%")
        ->orWhere('detail', 'like', "%{$query}%")
        ->paginate(self::ITEMS_PER_PAGE);

    // Clear and refresh the cache
    $this->clearAllProductCache();
    $this->refreshProductCache();

    return view('products.index', compact('products'))
        ->with('i', (request()->input('page', 1) - 1) * self::ITEMS_PER_PAGE);
}

}
