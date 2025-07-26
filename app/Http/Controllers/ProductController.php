<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis; // Import Redis Facade

class ProductController extends Controller
{
     // Define the cache key for all products
    const ALL_PRODUCTS_CACHE_KEY = 'all_products';
    // Define the cache expiration time in seconds (e.g., 1 hour)
    const CACHE_EXPIRATION_SECONDS = 60; // 60
     // Define items per page
    const ITEMS_PER_PAGE = 5;

      /**
     * Display a listing of the resource.
     * Fetches products from Redis cache, if not found, retrieves from DB and caches it.
     */
    public function index()
    {

    $perPage = 5;
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
        
    } else {
        $products = Product::latest()->paginate($perPage);
        $cacheData = [
            'data' => $products->items(),
            'total' => $products->total(),
            'per_page' => $products->perPage(),
            'current_page' => $products->currentPage(),
        ];
        Redis::setex($cacheKey, self::CACHE_EXPIRATION_SECONDS, json_encode($cacheData));
    }

    return view('products.index', compact('products'))
         ->with('i', ($page - 1) * $perPage);

        // $products = Product::latest()->paginate(5);

        // return view('products.index',compact('products'));
            // ->with('i', (request()->input('page', 1) - 1) * 5);
     
        // return view('products.index',compact('products'));   

    //    Check if products are cached
    // Try to get from Redis cache first
    //     $products = Redis::get('products:all');
        
    //     if ($products) {
    //         $products = json_decode($products, true);
    //         $source = 'Redis Cache';
    //     } else {
    //         // If not in cache, get from database
    //         $products = Product::all()->toArray();
    //         // Store in Redis for 1 hour (3600 seconds)
    //         Redis::setex('products:all', 3600, json_encode($products));
    //         $source = 'Database';
    //     }
    //     return response()->json([
    //         'message' => 'Products retrieved successfully.',
    //         'source' => $source,
    //         'products' => $products
    //     ]); 
    }

    /**
     * Store a newly created resource in storage.
     * Invalidates the 'all_products' cache after creation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $product = Product::create($request->all());

        // Invalidate the cache for all products using Redis::del
        Redis::del(self::ALL_PRODUCTS_CACHE_KEY);

        // return response()->json([
        //     'message' => 'Product created successfully.',
        //     'product' => $product
        // ], 201);

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
     * Update the specified resource in storage.
     * Invalidates the 'all_products' cache after update.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $product->update($request->all());

        // Invalidate the cache for all products using Redis::del
        Redis::del(self::ALL_PRODUCTS_CACHE_KEY);

        // return response()->json([
        //     'message' => 'Product updated successfully.',
        //     'product' => $product
        // ]);

         return redirect()->route('products.index')
                        ->with('success','Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     * Invalidates the 'all_products' cache after deletion.
     */
    public function destroy(Product $product)
    {
         $product->delete();

        // Invalidate the cache for all products using Redis::del
        Redis::del(self::ALL_PRODUCTS_CACHE_KEY);

        // return response()->json([
        //     'message' => 'Product deleted successfully.'
        // ], 204);

        return redirect()->route('products.index')

                        ->with('success','Product deleted successfully');
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
}
