<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Customer;
use App\Models\StockMovement;
use App\Models\Category;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CacheService
{
    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 60; // 1 hour
    const CACHE_DURATION_SHORT = 15; // 15 minutes
    const CACHE_DURATION_LONG = 1440; // 24 hours

    /**
     * Cache tags for easier invalidation
     */
    const TAG_DASHBOARD = 'dashboard';
    const TAG_INVENTORY = 'inventory';
    const TAG_PRODUCTS = 'products';
    const TAG_CUSTOMERS = 'customers';
    const TAG_SALES = 'sales';
    const TAG_REPORTS = 'reports';

    /**
     * Get dashboard statistics with caching
     *
     * @return array
     */
    public static function getDashboardStats()
    {
        return Cache::remember('dashboard_stats', self::CACHE_DURATION_SHORT, function () {
            // Today's sales
            $todaySales = Invoice::whereDate('created_at', Carbon::today())->sum('total');
            
            // Product count
            $productsCount = Product::count();
            
            // Customer count
            $customersCount = Customer::count();
            
            // Low stock products count
            $lowStockCount = Product::where('is_active', true)
                ->whereRaw('stock_quantity <= alert_quantity')
                ->where('alert_quantity', '>', 0)
                ->count();
            
            // Stock movements today
            $todayMovementsCount = StockMovement::whereDate('created_at', Carbon::today())->count();
            
            // Calculate total stock value
            $totalStockValue = Product::where('products.is_active', true)
                ->leftJoin('purchase_items', function($join) {
                    $join->on('products.id', '=', 'purchase_items.product_id')
                        ->whereRaw('purchase_items.id = (
                            SELECT id FROM purchase_items 
                            WHERE product_id = products.id 
                            ORDER BY created_at DESC 
                            LIMIT 1
                        )');
                })
                ->selectRaw('COALESCE(SUM(products.stock_quantity * COALESCE(purchase_items.purchase_price, 0)), 0) as total_value')
                ->first()
                ->total_value;
                
            return [
                'todaySales' => $todaySales,
                'productsCount' => $productsCount,
                'customersCount' => $customersCount,
                'lowStockCount' => $lowStockCount,
                'todayMovementsCount' => $todayMovementsCount,
                'totalStockValue' => $totalStockValue,
            ];
        });
    }

    /**
     * Get customer statistics with caching
     * 
     * @return array
     */
    public static function getCustomerStats()
    {
        return Cache::remember('customers:stats', self::CACHE_DURATION_SHORT, function () {
            return [
                'total_customers' => Customer::where('id', '!=', 1)->count(),
                'customers_with_balance' => Customer::where('id', '!=', 1)->where('credit_balance', '!=', 0)->count(),
                'total_balance' => Customer::where('id', '!=', 1)->sum('credit_balance'),
                'today_payments' => DB::table('customer_payments')
                    ->whereDate('created_at', today())
                    ->sum('amount')
            ];
        });
    }

    /**
     * Get low stock products with caching
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getLowStockProducts($limit = null)
    {
        $cacheKey = 'inventory:products:low_stock' . ($limit ? ':' . $limit : '');
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($limit) {
            $query = Product::with(['category', 'mainUnit'])
                ->whereRaw('stock_quantity <= alert_quantity')
                ->where('is_active', true)
                ->where('alert_quantity', '>', 0)
                ->orderBy('stock_quantity');
                
            if ($limit) {
                return $query->limit($limit)->get();
            }
            
            return $query->get();
        });
    }

    /**
     * Get recent stock movements with caching
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentStockMovements($limit = 10)
    {
        return Cache::remember('inventory:stock_movements:recent:' . $limit, self::CACHE_DURATION_SHORT, function () use ($limit) {
            return StockMovement::with(['product', 'unit', 'employee'])
                ->latest()
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get top selling products with caching
     * 
     * @param int $days The number of days to look back
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getTopSellingProducts($days = 30, $limit = 5)
    {
        $cacheKey = "products:top_selling:{$days}:{$limit}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($days, $limit) {
            return DB::table('invoice_items')
                ->join('products', 'invoice_items.product_id', '=', 'products.id')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoices.created_at', '>=', now()->subDays($days))
                ->select(
                    'products.id',
                    'products.name',
                    'products.barcode',
                    'products.stock_quantity',
                    DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                    DB::raw('SUM(invoice_items.total_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT invoices.id) as invoices_count')
                )
                ->groupBy('products.id', 'products.name', 'products.barcode', 'products.stock_quantity')
                ->orderByDesc('total_quantity')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get category statistics with caching
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function getCategoryStats()
    {
        return Cache::remember('categories:stats', self::CACHE_DURATION, function () {
            return Category::all()->map(function ($category) {
                $productsCount = $category->products()->count();
                $activeProductsCount = $category->products()->where('is_active', true)->count();
                $totalStock = $category->products()->sum('stock_quantity');
                $lowStockCount = $category->products()
                    ->whereRaw('stock_quantity <= alert_quantity')
                    ->where('alert_quantity', '>', 0)
                    ->count();
                
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'is_active' => $category->is_active,
                    'products_count' => $productsCount,
                    'active_products_count' => $activeProductsCount,
                    'total_stock' => $totalStock,
                    'low_stock_count' => $lowStockCount
                ];
            });
        });
    }

    /**
     * Get sales report data with caching
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string $groupBy
     * @param int|null $customerId
     * @param string|null $invoiceType
     * @return array
     */
    public static function getSalesReportData($startDate, $endDate, $groupBy = 'daily', $customerId = null, $invoiceType = null)
    {
        // Generate a unique cache key based on the parameters
        $cacheKey = "reports:sales:" . md5($startDate . $endDate . $groupBy . $customerId . $invoiceType);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($startDate, $endDate, $groupBy, $customerId, $invoiceType) {
            // Parse dates
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
            
            // Base query for invoices in the period
            $invoiceQuery = Invoice::whereBetween('created_at', [$startDate, $endDate]);
            
            // Apply filters
            if ($customerId) {
                $invoiceQuery->where('customer_id', $customerId);
            }
            
            if ($invoiceType) {
                $invoiceQuery->where('type', $invoiceType);
            }
            
            // Get sales summary
            $summary = $invoiceQuery->select(
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total) as total_sales'),
                DB::raw('SUM(profit) as total_profit'),
                DB::raw('AVG(total) as average_invoice')
            )->first();
            
            // Get total items sold
            $totalItems = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->whereBetween('invoices.created_at', [$startDate, $endDate]);
                
            if ($customerId) {
                $totalItems->where('invoices.customer_id', $customerId);
            }
            
            if ($invoiceType) {
                $totalItems->where('invoices.type', $invoiceType);
            }
            
            $totalItemsCount = $totalItems->sum('invoice_items.quantity');
            
            // Format summary data
            $summaryData = [
                'invoice_count' => $summary->invoice_count ?? 0,
                'total_sales' => $summary->total_sales ?? 0,
                'total_profit' => $summary->total_profit ?? 0,
                'total_items' => $totalItemsCount ?? 0,
                'average_invoice' => $summary->average_invoice ?? 0,
                'profit_margin' => ($summary->total_sales > 0) ? (($summary->total_profit / $summary->total_sales) * 100) : 0
            ];
            
            // Get sales data grouped by time period
            $salesByTimeData = [];
            
            switch ($groupBy) {
                case 'daily':
                    $salesByTimeData = $invoiceQuery->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total) as total_sales'),
                        DB::raw('SUM(profit) as total_profit'),
                        DB::raw('AVG(total) as average_order_value')
                    )->groupBy('date')->orderBy('date')->get();
                    break;
                    
                case 'weekly':
                    $salesByTimeData = $invoiceQuery->select(
                        DB::raw('YEARWEEK(created_at, 1) as yearweek'),
                        DB::raw('MIN(DATE(created_at)) as week_start'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total) as total_sales'),
                        DB::raw('SUM(profit) as total_profit'),
                        DB::raw('AVG(total) as average_order_value')
                    )->groupBy('yearweek')->orderBy('yearweek')->get();
                    
                    // Format week labels
                    $salesByTimeData->transform(function($item) {
                        $weekStart = Carbon::parse($item->week_start);
                        $weekEnd = (clone $weekStart)->addDays(6);
                        $item->week_label = $weekStart->format('Y-m-d') . ' - ' . $weekEnd->format('Y-m-d');
                        return $item;
                    });
                    break;
                    
                case 'monthly':
                    $salesByTimeData = $invoiceQuery->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total) as total_sales'),
                        DB::raw('SUM(profit) as total_profit'),
                        DB::raw('AVG(total) as average_order_value')
                    )->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get();
                    
                    // Format month labels
                    $salesByTimeData->transform(function($item) {
                        $date = Carbon::createFromDate($item->year, $item->month, 1);
                        $item->date = $date->format('Y-m'); // Store for sorting
                        $item->month_name = $date->translatedFormat('F Y');
                        return $item;
                    });
                    break;
            }
            
            // Prepare return data
            return [
                'summary' => $summaryData,
                'salesByTime' => $salesByTimeData,
            ];
        });
    }

    /**
     * Clear specific cache by keys associated with given tags
     *
     * @param string|array $tags
     * @return void
     */
    public static function clearCache($tags)
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        
        // Instead of using Cache::tags()->flush(), we'll clear each key
        // Note: This is less efficient but works with non-taggable caches
        
        // Collect patterns based on tags
        $patterns = [];
        foreach ($tags as $tag) {
            switch ($tag) {
                case self::TAG_DASHBOARD:
                    $patterns[] = 'dashboard_stats';
                    break;
                case self::TAG_INVENTORY:
                    $patterns[] = 'inventory:*';
                    break;
                case self::TAG_PRODUCTS:
                    $patterns[] = 'products:*';
                    break;
                case self::TAG_CUSTOMERS:
                    $patterns[] = 'customers:*';
                    break;
                case self::TAG_SALES:
                    $patterns[] = 'sales:*';
                    break;
                case self::TAG_REPORTS:
                    $patterns[] = 'reports:*';
                    break;
            }
        }
        
        // For each pattern, attempt to clear matching keys
        foreach ($patterns as $pattern) {
            // For exact matches, clear directly
            if (strpos($pattern, '*') === false) {
                Cache::forget($pattern);
            } else {
                // For wildcard patterns, since Laravel doesn't provide a way to clear
                // by pattern, we need to manually clear the specific keys we know about
                self::clearSpecificKeysMatchingPattern($pattern);
            }
        }
    }
    
    /**
     * Clear specific cache keys that match a pattern
     * 
     * @param string $pattern
     * @return void
     */
    private static function clearSpecificKeysMatchingPattern($pattern)
    {
        // Convert pattern to regex
        $regex = '/^' . str_replace(['*'], ['.*'], $pattern) . '$/';
        
        // Define known keys matching each pattern type
        $knownKeys = [
            'dashboard_stats' => ['dashboard_stats'],
            'inventory:*' => ['inventory:products:low_stock', 'inventory:stock_movements:recent:10'],
            'products:*' => ['products:top_selling:30:5'],
            'customers:*' => ['customers:stats'],
            'reports:*' => [], // Will be handled by specific report clearing methods
            'sales:*' => []     // Will be handled by specific sales clearing methods
        ];
        
        // Get base pattern without wildcards
        $basePattern = strtok($pattern, '*');
        
        // If we have defined keys for this pattern, clear them
        if (isset($knownKeys[$pattern])) {
            foreach ($knownKeys[$pattern] as $key) {
                Cache::forget($key);
            }
        } else {
            // For dynamic patterns, try to match and clear
            foreach ($knownKeys as $patternGroup => $keys) {
                if (strpos($patternGroup, $basePattern) === 0) {
                    foreach ($keys as $key) {
                        if (preg_match($regex, $key)) {
                            Cache::forget($key);
                        }
                    }
                }
            }
        }
    }

    /**
     * Clear all application cache
     *
     * @return void
     */
    public static function clearAllCache()
    {
        Cache::flush();
    }

    /**
     * Get filtered product listings with caching
     * 
     * @param array $filters Associative array of filter parameters
     * @param int $perPage Number of items per page
     * @param string $sortBy Column to sort by
     * @param string $sortDirection Sort direction (asc/desc)
     * @return array
     */
    public static function getFilteredProducts(array $filters = [], $perPage = 15, $sortBy = 'name', $sortDirection = 'asc')
    {
        // Generate a unique cache key based on all parameters
        $cacheKey = 'products_' . md5(json_encode($filters) . $perPage . $sortBy . $sortDirection);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($filters, $perPage, $sortBy, $sortDirection) {
            $query = Product::with(['category', 'mainUnit']);
            
            // Apply filters
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }
            
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }
            
            if (isset($filters['stock_status'])) {
                switch ($filters['stock_status']) {
                    case 'in_stock':
                        $query->where('stock_quantity', '>', 0);
                        break;
                    case 'out_of_stock':
                        $query->where('stock_quantity', '<=', 0);
                        break;
                    case 'low_stock':
                        $query->whereRaw('stock_quantity <= alert_quantity')
                              ->where('alert_quantity', '>', 0)
                              ->where('stock_quantity', '>', 0);
                        break;
                }
            }
            
            // Apply sorting
            $query->orderBy($sortBy, $sortDirection);
            
            // Get paginated results
            $products = $query->paginate($perPage);
            
            // Format for response
            return [
                'products' => $products->items(),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                ]
            ];
        });
    }

    /**
     * Get a product with all its related data using caching
     * 
     * @param int $productId
     * @return \App\Models\Product
     */
    public static function getProductWithDetails($productId)
    {
        return Cache::remember('product_' . $productId, self::CACHE_DURATION, function () use ($productId) {
            return Product::with([
                'category', 
                'mainUnit', 
                'units',
                'stockMovements' => function($query) {
                    $query->latest()->limit(10);
                }
            ])->findOrFail($productId);
        });
    }

    /**
     * Clear specific cache key
     *
     * @param string $cacheKey
     * @return void
     */
    public static function clearCacheKey($cacheKey)
    {
        Cache::forget($cacheKey);
    }
    
    /**
     * Clear product related caches
     *
     * @return void
     */
    public static function clearProductCaches()
    {
        self::clearCache([self::TAG_PRODUCTS, self::TAG_INVENTORY]);
    }
} 