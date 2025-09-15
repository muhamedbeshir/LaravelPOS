<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\ProductLog;
class PurchaseApiController extends Controller
{
    /**
     * Get all purchases with pagination
     */
    public function getAllPurchases(Request $request)
    {
        try {
            $query = Purchase::with(['supplier', 'employee']);
            
            // Search functionality
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->where(function($q) use ($searchTerm) {
                    // Search by invoice number
                    $q->where('invoice_number', 'like', "%{$searchTerm}%");
                    
                    // Search by supplier name
                    $q->orWhereHas('supplier', function($sq) use ($searchTerm) {
                        $sq->where('name', 'like', "%{$searchTerm}%")
                           ->orWhere('company_name', 'like', "%{$searchTerm}%");
                    });
                });
            }
            
            // Filter by supplier
            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->input('supplier_id'));
            }
            
            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('purchase_date', '>=', $request->input('start_date'));
            }
            
            if ($request->has('end_date')) {
                $query->whereDate('purchase_date', '<=', $request->input('end_date'));
            }
            
            // Filter by status (with remaining balance)
            if ($request->has('has_balance') && $request->boolean('has_balance')) {
                $query->where('remaining_amount', '>', 0);
            }
            
            // Sort by most recent first
            $query->latest();
            
            // Paginate results
            $perPage = $request->input('per_page', 15);
            $purchases = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'purchases' => $purchases
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching purchases: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching purchases',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific purchase with its details
     */
    public function getPurchase($id)
    {
        try {
            $purchase = Purchase::with(['supplier', 'employee', 'items.product', 'items.unit'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'purchase' => $purchase
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching purchase: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found or error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Create a new purchase
     */
    public function storePurchase(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required|exists:suppliers,id',
                'employee_id' => 'required|exists:employees,id',
                'purchase_date' => 'required|date',
                'total_amount' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.unit_id' => 'required|exists:units,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.purchase_price' => 'required|numeric|min:0',
                'items.*.selling_price' => 'required|numeric|min:0',
                'items.*.production_date' => 'nullable|date',
                'items.*.expiry_date' => 'nullable|date|after:production_date',
                'items.*.alert_days_before_expiry' => 'nullable|integer|min:1'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            // Create purchase
            $purchase = new Purchase();
            $purchase->supplier_id = $request->supplier_id;
            $purchase->employee_id = $request->employee_id;
            $purchase->total_amount = $request->total_amount;
            $purchase->paid_amount = $request->paid_amount;
            $purchase->remaining_amount = $request->total_amount - $request->paid_amount;
            $purchase->invoice_number = $purchase->generateInvoiceNumber();
            $purchase->purchase_date = $request->purchase_date;
            $purchase->notes = $request->notes;
            $purchase->save();
            
            // Create purchase items and update inventory
            foreach ($request->items as $item) {
                $purchaseItem = new PurchaseItem();
                $purchaseItem->purchase_id = $purchase->id;
                $purchaseItem->product_id = $item['product_id'];
                $purchaseItem->unit_id = $item['unit_id'];
                $purchaseItem->quantity = $item['quantity'];
                $purchaseItem->purchase_price = $item['purchase_price'];
                $purchaseItem->selling_price = $item['selling_price'];
                $purchaseItem->production_date = $item['production_date'] ?? null;
                $purchaseItem->expiry_date = $item['expiry_date'] ?? null;
                $purchaseItem->alert_days_before_expiry = $item['alert_days_before_expiry'] ?? 30;
                $purchaseItem->calculateProfit();
                $purchaseItem->save();
                
                // Update product stock
                $product = Product::find($item['product_id']);
                $product->updateStock(
                    $item['quantity'],
                    $item['unit_id'],
                    'add',
                    [
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'employee_id' => $request->employee_id,
                        'notes' => 'إضافة مخزون من فاتورة شراء رقم ' . $purchase->invoice_number
                    ]
                );

                ProductLog::create([
                    'product_id' => $item['product_id'],
                    'event' => 'تم إنشاء فاتورة شراء', // Purchase invoice created
                    'quantity' => $item['quantity'],
                    'reference' => 'فاتورة شراء #' . $purchase->invoice_number,
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Purchase created successfully',
                'purchase' => $purchase->fresh(['supplier', 'employee', 'items.product', 'items.unit'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating purchase: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating purchase',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update purchase payment
     */
    public function updatePayment(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'paid_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $purchase = Purchase::findOrFail($id);
            
            // Ensure paid amount does not exceed total amount
            if ($request->paid_amount > $purchase->total_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paid amount cannot exceed total amount'
                ], 422);
            }
            
            // Update payment data
            $purchase->paid_amount = $request->paid_amount;
            $purchase->remaining_amount = $purchase->total_amount - $request->paid_amount;
            
            // Update notes if provided
            if ($request->has('notes')) {
                $purchase->notes = $request->notes;
            }
            
            $purchase->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'purchase' => $purchase->fresh()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating purchase payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating purchase payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get products near expiry
     */
    public function getNearExpiryItems(Request $request)
    {
        try {
            $daysThreshold = $request->input('days', 30);
            
            $nearExpiryItems = PurchaseItem::whereNotNull('expiry_date')
                ->with(['product', 'purchase'])
                ->whereRaw('DATEDIFF(expiry_date, CURDATE()) <= alert_days_before_expiry')
                ->where('expiry_date', '>', Carbon::now())
                ->get();
                
            return response()->json([
                'success' => true,
                'items' => $nearExpiryItems
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching near expiry items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching near expiry items',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get purchase statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            // Date range filter
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
            
            // Get total purchases
            $totalPurchases = Purchase::whereBetween('purchase_date', [$startDate, $endDate])->count();
            
            // Get total amount
            $totalAmount = Purchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('total_amount');
            
            // Get total paid amount
            $totalPaid = Purchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('paid_amount');
            
            // Get total remaining amount
            $totalRemaining = Purchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('remaining_amount');
            
            // Get top suppliers
            $topSuppliers = Purchase::with('supplier')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->select('supplier_id', DB::raw('SUM(total_amount) as total_amount'), DB::raw('COUNT(*) as purchase_count'))
                ->groupBy('supplier_id')
                ->orderByDesc('total_amount')
                ->limit(5)
                ->get();
                
            // Get top purchased products
            $topProducts = PurchaseItem::with('product')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                ->whereBetween('purchases.purchase_date', [$startDate, $endDate])
                ->select(
                    'purchase_items.product_id',
                    DB::raw('SUM(purchase_items.quantity) as total_quantity'),
                    DB::raw('SUM(purchase_items.quantity * purchase_items.purchase_price) as total_amount')
                )
                ->groupBy('purchase_items.product_id')
                ->orderByDesc('total_amount')
                ->limit(5)
                ->get();
                
            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_purchases' => $totalPurchases,
                    'total_amount' => $totalAmount,
                    'total_paid' => $totalPaid,
                    'total_remaining' => $totalRemaining,
                    'top_suppliers' => $topSuppliers,
                    'top_products' => $topProducts
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching purchase statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching purchase statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get profit analytics
     */
    public function getProfitAnalytics()
    {
        try {
            $topProfitableProducts = PurchaseItem::with('product')
                ->select(
                    'product_id',
                    DB::raw('SUM(expected_profit) as total_profit'),
                    DB::raw('AVG(profit_percentage) as avg_profit_percentage')
                )
                ->groupBy('product_id')
                ->orderByDesc('total_profit')
                ->limit(10)
                ->get();
                
            return response()->json([
                'success' => true,
                'profit_analytics' => $topProfitableProducts
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching profit analytics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching profit analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 