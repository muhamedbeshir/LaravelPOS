<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductLog;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class InventoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['products' => function($query) {
            $query->where('is_active', true);
        }])->get();

        // Get low stock products from cache
        $lowStockProducts = CacheService::getLowStockProducts();

        // Get recent movements from cache
        $recentMovements = CacheService::getRecentStockMovements(10);

        return view('inventory.index', compact('categories', 'lowStockProducts', 'recentMovements'));
    }

    public function stockAdjustment()
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.stock_adjustment', compact('products'));
    }

    public function saveStockAdjustment(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'adjustment_type' => 'required|in:add,subtract',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();
            
            $product = Product::with('mainUnit')->findOrFail($request->product_id);
            $quantity = abs($request->quantity);

            if ($request->adjustment_type === 'subtract' && $product->stock_quantity < $quantity) {
                return back()->with('error', 'The requested quantity exceeds available inventory');
            }

            $beforeQuantity = $product->stock_quantity;
            
            if ($request->adjustment_type === 'add') {
                $product->stock_quantity += $quantity;
                $movementType = 'in';
            } else {
                $product->stock_quantity -= $quantity;
                $movementType = 'out';
            }
            
            $afterQuantity = $product->stock_quantity;

            // Save product
            $product->save();

            // Create stock adjustment record
            $stockAdjustment = \App\Models\StockAdjustment::create([
                'product_id' => $product->id,
                'unit_id' => $product->mainUnit->id,
                'quantity' => $quantity,
                'adjustment_type' => $request->adjustment_type,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'employee_id' => auth()->id(),
                'notes' => $request->notes
            ]);

            // Create stock movement with proper reference
            \App\Models\ProductLog::create([
                'product_id' => $product->id,
                'event' => 'تعديل المخزون', // Stock adjustment
                'quantity' => $quantity * ($request->adjustment_type === 'add' ? 1 : -1),
                'reference' => 'تعديل يدوي',
            ]);
            
            DB::commit();

            // Clear relevant caches after stock adjustment
            CacheService::clearCache([
                CacheService::TAG_INVENTORY, 
                CacheService::TAG_PRODUCTS, 
                CacheService::TAG_DASHBOARD
            ]);

            return back()->with('success', 'Inventory adjustment completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in stock adjustment: ' . $e->getMessage());
            return back()->with('error', 'An error occurred during inventory adjustment: ' . $e->getMessage());
        }
    }

    public function stockCount()
    {
        $categories = Category::with(['products' => function($query) {
            $query->where('is_active', true);
        }])->get();

        return view('inventory.stock_count', compact('categories'));
    }

    public function saveStockCount(Request $request)
    {
        $request->validate([
            'counts' => 'required|array',
            'counts.*.product_id' => 'required|exists:products,id',
            'counts.*.actual_quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            // Collect all product IDs for batch loading
            $productIds = collect($request->counts)->pluck('product_id')->unique()->toArray();
            
            // Load all products in a single query
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            
            // Prepare stock movements for batch insert
            $stockMovements = [];
            $now = now();
            $employeeId = auth()->id();
            
            // Start transaction for all database operations
            DB::beginTransaction();
            
            foreach ($request->counts as $count) {
                $productId = $count['product_id'];
                $product = $products[$productId] ?? null;
                
                if (!$product) {
                    continue; // Skip if product not found
                }
                
                $beforeQuantity = $product->stock_quantity;
                $actualQuantity = $count['actual_quantity'];
                $difference = $actualQuantity - $beforeQuantity;
                
                // Only process if there's a difference to avoid unnecessary updates
                if ($difference != 0) {
                    // Update product stock quantity
                    $product->stock_quantity = $actualQuantity;
                    
                    // Prepare stock movement record
                    $stockMovements[] = [
                        'product_id' => $product->id,
                        'unit_id' => $product->main_unit_id,
                        'quantity' => abs($difference),
                        'before_quantity' => $beforeQuantity,
                        'after_quantity' => $actualQuantity,
                        'movement_type' => $difference > 0 ? 'in' : 'out',
                        'reference_type' => 'stock_count',
                        'reference_id' => null,
                        'employee_id' => $employeeId,
                        'notes' => $request->notes,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }
            
            // Save all product updates in a single loop
            foreach ($products as $product) {
                $product->save();
            }
            
            // Bulk insert all stock movements in a single query
            if (!empty($stockMovements)) {
                StockMovement::insert($stockMovements);
            }
            
            DB::commit();
            
            // Clear relevant caches after stock count update
            CacheService::clearCache([
                CacheService::TAG_INVENTORY, 
                CacheService::TAG_PRODUCTS, 
                CacheService::TAG_DASHBOARD
            ]);
            
            return back()->with('success', 'Stock count saved successfully. Updated ' . count($stockMovements) . ' products.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving stock count: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while saving stock count: ' . $e->getMessage());
        }
    }

    public function report(Request $request)
    {
        // Report generation should usually get fresh data, but we can cache product list
        $query = StockMovement::with(['product', 'unit', 'employee']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        
        // For debugging purposes - log the query
        \Log::info('Inventory report query', [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'product_id' => $request->product_id,
            'movement_type' => $request->movement_type,
            'query_sql' => $query->toSql(),
            'query_bindings' => $query->getBindings()
        ]);
        
        $movements = $query->latest()->paginate(20);
        
        // Log the movements count for debugging
        \Log::info('Movements found: ' . $movements->total());
        
        // Get products list from cache without using tags
        $products = Cache::remember('active_products_list', CacheService::CACHE_DURATION, function() {
                return Product::where('is_active', true)->orderBy('name')->get();
            });

        return view('inventory.report', compact('movements', 'products'));
    }
    
    /**
     * Display the stock report view with products and their current quantities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function stockReport(Request $request)
    {
        // Add more detailed logging for debugging
        \Log::info('========== STOCK REPORT DEBUGGING ==========');
        \Log::info('Request method: ' . $request->method());
        \Log::info('Is AJAX request: ' . ($request->ajax() ? 'Yes' : 'No'));
        \Log::info('Has ajax param: ' . ($request->filled('ajax') ? 'Yes' : 'No'));
        \Log::info('Request parameters:', $request->all());
        
        try {
            $categories = Category::all();
            
            $query = Product::with(['category', 'mainUnit'])
                ->when($request->filled('category_id'), function ($q) use ($request) {
                    return $q->where('category_id', $request->category_id);
                })
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    return $q->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('stock_status'), function ($q) use ($request) {
                    if ($request->stock_status === 'in_stock') {
                        return $q->where('stock_quantity', '>', 0);
                    } elseif ($request->stock_status === 'out_of_stock') {
                        return $q->where('stock_quantity', '<=', 0);
                    } elseif ($request->stock_status === 'low_stock') {
                        return $q->whereRaw('stock_quantity <= alert_quantity AND stock_quantity > 0 AND alert_quantity > 0');
                    }
                    return $q;
                });
            
            $products = $query->latest()->paginate(15);
            
            // If it's an AJAX request, return JSON response
            if ($request->ajax() || $request->filled('ajax')) {
                // Check for debug mode
                if ($request->filled('debug_mode')) {
                    \Log::info('Debug mode enabled, returning simplified response');
                    return response()->json([
                        'success' => true,
                        'debug' => true,
                        'request_data' => $request->all(),
                        'products_count' => $products->count(),
                        'time' => now()->toDateTimeString()
                    ]);
                }
                
                // Log AJAX request details for debugging
                \Log::info('AJAX request details', [
                    'search' => $request->search,
                    'category_id' => $request->category_id,
                    'stock_status' => $request->stock_status,
                    'products_count' => $products->count()
                ]);
                
                try {
                    // Get statistics for real-time card updates
                    $stats = [
                        'total' => Product::count(),
                        'in_stock' => Product::where('stock_quantity', '>', 0)->count(),
                        'out_of_stock' => Product::where('stock_quantity', '<=', 0)->count()
                    ];
                    
                    // Render the table HTML
                    try {
                        $html = view('inventory.partials.products_table', compact('products'))->render();
                        \Log::info('HTML rendered successfully, length: ' . strlen($html));
                    } catch (\Exception $e) {
                        \Log::error('Error rendering products table partial', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        // If partial fails to render, create a basic HTML error message
                        $html = '<div class="alert alert-danger m-3">
                                    <h5><i class="fas fa-exclamation-triangle me-2"></i>حدث خطأ أثناء تحميل البيانات</h5>
                                    <p>يرجى تحديث الصفحة والمحاولة مرة أخرى.</p>
                                </div>';
                    }
                    
                    $response = [
                        'html' => $html,
                        'stats' => $stats,
                        'debug' => [
                            'timestamp' => now()->toDateTimeString(),
                            'products_count' => $products->count(),
                            'html_length' => strlen($html)
                        ]
                    ];
                    
                    \Log::info('Sending JSON response', [
                        'response_size' => strlen(json_encode($response)),
                        'has_html' => isset($response['html']),
                        'has_stats' => isset($response['stats']),
                    ]);
                    
                    return response()->json($response);
                } catch (\Exception $e) {
                    \Log::error('Error in AJAX response generation', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return response()->json([
                        'error' => 'Error processing request: ' . $e->getMessage()
                    ], 500);
                }
            }
            
            return view('inventory.stock_report', compact('products', 'categories'));
        } catch (\Exception $e) {
            \Log::error('Error in stockReport method', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax() || $request->filled('ajax')) {
                return response()->json([
                    'error' => 'Server error: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'حدث خطأ أثناء تحميل تقرير المخزون: ' . $e->getMessage());
        }
    }

    /**
     * Export inventory movements report with optimized batch processing and streaming response
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportReport(Request $request)
    {
        // Build query with the same filters as the report method
        $query = StockMovement::with(['product', 'unit', 'employee']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        
        // Set up headers for CSV download
        $filename = 'inventory_movements_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        // Use streaming response to handle large datasets efficiently
        return response()->stream(function() use ($query) {
            // Open output stream
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM to fix Excel display of Arabic text
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add CSV headers
            fputcsv($output, [
                'ID',
                'Date',
                'Product',
                'Unit',
                'Type',
                'Quantity',
                'Before Quantity',
                'After Quantity',
                'Reference Type',
                'Reference ID',
                'Employee',
                'Notes'
            ]);
            
            // Process records in batches of 1000 to optimize memory usage
            $query->orderBy('id')->chunk(1000, function($movements) use ($output) {
                foreach ($movements as $movement) {
                    $movementType = '';
                    if ($movement->movement_type == 'in') {
                        $movementType = 'In';
                    } elseif ($movement->movement_type == 'out') {
                        $movementType = 'Out';
                    } elseif ($movement->movement_type == 'adjustment') {
                        $movementType = 'Adjustment';
                    }
                    
                    fputcsv($output, [
                        $movement->id,
                        $movement->created_at->format('Y-m-d H:i:s'),
                        $movement->product ? $movement->product->name : 'Unknown Product',
                        $movement->unit ? $movement->unit->name : 'Unknown Unit',
                        $movementType,
                        $movement->quantity,
                        $movement->before_quantity,
                        $movement->after_quantity,
                        $movement->reference_type,
                        $movement->reference_id,
                        $movement->employee ? $movement->employee->name : 'Unknown Employee',
                        $movement->notes
                    ]);
                }
            });
            
            fclose($output);
        }, 200, $headers);
    }

    /**
     * API: Get stock quantity for a product in a specific unit
     */
    public function getStockQuantityAjax($productId, $unitId)
    {
        $product = \App\Models\Product::with('units')->findOrFail($productId);
        $quantity = $product->getStockQuantity($unitId);
        return response()->json([
            'quantity' => number_format($quantity, 2)
        ]);
    }
} 