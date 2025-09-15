<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\ProductLog;

class InventoryApiController extends Controller
{
    /**
     * Get inventory dashboard data
     */
    public function getDashboard()
    {
        try {
            // Get low stock products
            $lowStockProducts = Product::with(['category', 'mainUnit'])
                ->whereRaw('stock_quantity <= alert_quantity')
                ->where('is_active', true)
                ->where('alert_quantity', '>', 0)
                ->get();
            
            // Get recent stock movements
            $recentMovements = StockMovement::with(['product', 'unit', 'employee'])
                ->latest()
                ->take(10)
                ->get();
                
            // Get stock summary by category
            $stockByCategory = Category::with(['products' => function($query) {
                $query->where('is_active', true);
            }])
            ->get()
            ->map(function($category) {
                $totalItems = $category->products->count();
                $totalStock = $category->products->sum('stock_quantity');
                $lowStockItems = $category->products
                    ->where('stock_quantity', '<=', DB::raw('alert_quantity'))
                    ->where('alert_quantity', '>', 0)
                    ->count();
                
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_items' => $totalItems,
                    'total_stock' => $totalStock,
                    'low_stock_items' => $lowStockItems
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock_products' => $lowStockProducts,
                    'recent_movements' => $recentMovements,
                    'stock_by_category' => $stockByCategory
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching inventory dashboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching inventory dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Adjust stock quantity for a product
     */
    public function adjustStock(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'unit_id' => 'required|exists:product_units,id',
                'quantity' => 'required|numeric|min:0.01',
                'adjustment_type' => 'required|in:add,subtract',
                'notes' => 'nullable|string|max:500'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $product = Product::findOrFail($request->product_id);
            $unit = ProductUnit::where('id', $request->unit_id)
                ->where('product_id', $request->product_id)
                ->firstOrFail();
            
            // Convert quantity to main unit
            $quantityInMainUnit = abs($request->quantity) * $unit->conversion_factor;
            
            $beforeQuantity = $product->stock_quantity;
            
            // Check if there's enough stock for subtraction
            if ($request->adjustment_type === 'subtract' && $product->stock_quantity < $quantityInMainUnit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock for this operation',
                    'available_stock' => $product->stock_quantity,
                    'requested_stock' => $quantityInMainUnit
                ], 422);
            }
            
            // Update stock quantity
            if ($request->adjustment_type === 'add') {
                $product->stock_quantity += $quantityInMainUnit;
                $movementType = 'in';
            } else {
                $product->stock_quantity -= $quantityInMainUnit;
                $movementType = 'out';
            }
            
            $product->save();
            
            // Create stock adjustment record
            $stockAdjustment = \App\Models\StockAdjustment::create([
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => abs($request->quantity),
                'adjustment_type' => $request->adjustment_type,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $product->stock_quantity,
                'employee_id' => $request->employee_id ?? auth()->id(),
                'notes' => $request->notes
            ]);
            
            // Record stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => abs($request->quantity),
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $product->stock_quantity,
                'movement_type' => $movementType,
                'reference_type' => \App\Models\StockAdjustment::class,
                'reference_id' => $stockAdjustment->id,
                'employee_id' => $request->employee_id ?? auth()->id(),
                'notes' => $request->notes
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'product' => $product->fresh(),
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $product->stock_quantity
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adjusting stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adjusting stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Perform stock count for multiple products
     */
    public function stockCount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'counts' => 'required|array',
                'counts.*.product_id' => 'required|exists:products,id',
                'counts.*.unit_id' => 'required|exists:product_units,id',
                'counts.*.actual_quantity' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
                'employee_id' => 'nullable|exists:employees,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $results = [];
            
            foreach ($request->counts as $count) {
                $product = Product::findOrFail($count['product_id']);
                $unit = ProductUnit::where('id', $count['unit_id'])
                    ->where('product_id', $count['product_id'])
                    ->firstOrFail();
                
                // Convert count to main unit
                $actualQuantityInMainUnit = $count['actual_quantity'] * $unit->conversion_factor;
                
                $beforeQuantity = $product->stock_quantity;
                $difference = $actualQuantityInMainUnit - $beforeQuantity;
                
                if ($difference != 0) {
                    $product->stock_quantity = $actualQuantityInMainUnit;
                    $product->save();
                    
                    // Record stock movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'unit_id' => $unit->id,
                        'quantity' => abs($difference) / $unit->conversion_factor, // Convert back to requested unit
                        'before_quantity' => $beforeQuantity,
                        'after_quantity' => $product->stock_quantity,
                        'movement_type' => $difference > 0 ? 'in' : 'out',
                        'reference_type' => 'stock_count',
                        'reference_id' => null,
                        'employee_id' => $request->employee_id ?? auth()->id(),
                        'notes' => $request->notes
                    ]);
                    
                    $results[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'before_quantity' => $beforeQuantity,
                        'after_quantity' => $product->stock_quantity,
                        'difference' => $difference,
                        'adjusted' => true
                    ];

                    ProductLog::create([
                        'product_id' => $product->id,
                        'event' => 'تعديل المخزون', // Stock adjustment
                        'quantity' => $difference,
                        'reference' => 'تعديل API',
                    ]);
                } else {
                    $results[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $product->stock_quantity,
                        'adjusted' => false
                    ];
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock count completed successfully',
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error performing stock count: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing stock count',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get stock movement history
     */
    public function getStockMovements(Request $request)
    {
        try {
            $query = StockMovement::with(['product', 'unit', 'employee']);
            
            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }
            
            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }
            
            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->input('product_id'));
            }
            
            // Filter by movement type
            if ($request->has('movement_type')) {
                $query->where('movement_type', $request->input('movement_type'));
            }
            
            // Filter by reference type
            if ($request->has('reference_type')) {
                $query->where('reference_type', $request->input('reference_type'));
            }
            
            // Order by created_at descending
            $query->orderBy('created_at', 'desc');
            
            // Paginate results
            $perPage = $request->input('per_page', 15);
            $movements = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'movements' => $movements
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching stock movements: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stock movements',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get low stock products
     */
    public function getLowStockProducts()
    {
        try {
            $lowStockProducts = Product::with(['category', 'mainUnit'])
                ->whereRaw('stock_quantity <= alert_quantity')
                ->where('is_active', true)
                ->where('alert_quantity', '>', 0)
                ->get();
                
            return response()->json([
                'success' => true,
                'products' => $lowStockProducts
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching low stock products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching low stock products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 