<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PurchaseReturnApiController extends Controller
{
    /**
     * Get all purchase returns with pagination
     */
    public function getAllPurchaseReturns(Request $request)
    {
        try {
            $query = PurchaseReturn::with(['supplier', 'employee', 'purchase']);
            
            // Filter by supplier
            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->input('supplier_id'));
            }
            
            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('return_date', '>=', $request->input('start_date'));
            }
            
            if ($request->has('end_date')) {
                $query->whereDate('return_date', '<=', $request->input('end_date'));
            }
            
            // Sort by most recent first
            $query->latest();
            
            // Paginate results
            $perPage = $request->input('per_page', 15);
            $purchaseReturns = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'purchase_returns' => $purchaseReturns
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching purchase returns: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching purchase returns',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get purchase return details
     */
    public function getPurchaseReturn($id)
    {
        try {
            $purchaseReturn = PurchaseReturn::with(['supplier', 'employee', 'purchase', 'items.product', 'items.unit'])
                ->findOrFail($id);
                
            return response()->json([
                'success' => true,
                'purchase_return' => $purchaseReturn
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching purchase return: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching purchase return',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a new purchase return
     */
    public function createPurchaseReturn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required|exists:suppliers,id',
                'employee_id' => 'nullable|exists:employees,id',
                'purchase_id' => 'nullable|exists:purchases,id',
                'return_date' => 'required|date',
                'return_type' => 'required|in:full,partial,direct',
                'total_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.unit_id' => 'required|exists:units,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.purchase_price' => 'required|numeric|min:0',
                'items.*.reason' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            // Create purchase return
            $purchaseReturn = new PurchaseReturn();
            $purchaseReturn->supplier_id = $request->supplier_id;
            $purchaseReturn->employee_id = $request->employee_id;
            $purchaseReturn->purchase_id = $request->purchase_id;
            $purchaseReturn->return_date = $request->return_date;
            $purchaseReturn->return_type = $request->return_type;
            $purchaseReturn->total_amount = $request->total_amount;
            $purchaseReturn->return_number = $purchaseReturn->generateReturnNumber();
            $purchaseReturn->notes = $request->notes;
            
            // ربط المرتجع بالوردية الحالية
            $currentShift = \App\Models\Shift::getCurrentOpenShift();
            if ($currentShift) {
                $purchaseReturn->shift_id = $currentShift->id;
            }
            
            $purchaseReturn->save();
            
            // Create return items and update inventory
            foreach ($request->items as $item) {
                $returnItem = new PurchaseReturnItem();
                $returnItem->purchase_return_id = $purchaseReturn->id;
                $returnItem->product_id = $item['product_id'];
                $returnItem->unit_id = $item['unit_id'];
                $returnItem->quantity = $item['quantity'];
                $returnItem->purchase_price = $item['purchase_price'];
                $returnItem->reason = $item['reason'] ?? null;
                $returnItem->save();
                
                // Update product stock
                $product = Product::find($item['product_id']);
                
                // Find the unit conversion factor
                $productUnit = ProductUnit::where('product_id', $item['product_id'])
                    ->where('unit_id', $item['unit_id'])
                    ->first();
                
                if (!$productUnit) {
                    throw new \Exception('Product unit not found');
                }
                
                // Record stock movement for the returned items
                StockMovement::recordMovement([
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'movement_type' => 'out', // Decreasing stock for returns
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->id,
                    'employee_id' => $request->employee_id ?? auth()->id(),
                    'notes' => 'مرتجع مشتريات رقم: ' . $purchaseReturn->return_number
                ]);
                
                // Adjust the product stock quantity
                $quantityInMainUnit = $item['quantity'] * $productUnit->conversion_factor;
                $product->stock_quantity -= $quantityInMainUnit;
                $product->save();
            }
            
            // Update supplier balance
            $purchaseReturn->updateSupplierBalance();
            
            // Update the original purchase invoice if it exists
            if ($purchaseReturn->purchase_id) {
                $purchase = Purchase::find($purchaseReturn->purchase_id);
                if ($purchase) {
                    // Deduct return amount from purchase total and update remaining balance
                    $purchase->total_amount -= $purchaseReturn->total_amount;
                    $purchase->remaining_amount = $purchase->total_amount - $purchase->paid_amount;
                    
                    // Update status based on new remaining amount
                    if ($purchase->remaining_amount <= 0) {
                        $purchase->status = 'paid';
                    } elseif ($purchase->paid_amount > 0) {
                        $purchase->status = 'partially_paid';
                    } else {
                        $purchase->status = 'pending';
                    }
                    
                    $purchase->save();
                    
                    // If there's a supplier, update their amounts
                    if ($purchase->supplier) {
                        $purchase->supplier->updateAmounts();
                    }
                }
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Purchase return created successfully',
                'purchase_return' => $purchaseReturn->fresh(['supplier', 'employee', 'purchase', 'items.product', 'items.unit'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating purchase return: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating purchase return',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Return a full purchase
     */
    public function returnFullPurchase(Request $request, $purchaseId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string',
                'reason' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $purchase = Purchase::with(['items.product', 'items.unit'])->findOrFail($purchaseId);
            
            DB::beginTransaction();
            
            $purchaseReturn = new PurchaseReturn();
            $purchaseReturn->supplier_id = $purchase->supplier_id;
            $purchaseReturn->employee_id = $request->employee_id ?? auth()->id();
            $purchaseReturn->purchase_id = $purchase->id;
            $purchaseReturn->return_date = now();
            $purchaseReturn->return_type = 'full';
            $purchaseReturn->total_amount = $purchase->total_amount;
            $purchaseReturn->return_number = $purchaseReturn->generateReturnNumber();
            $purchaseReturn->notes = $request->notes ?? 'مرتجع كامل للفاتورة رقم: ' . $purchase->invoice_number;
            
            // ربط المرتجع بالوردية الحالية
            $currentShift = \App\Models\Shift::getCurrentOpenShift();
            if ($currentShift) {
                $purchaseReturn->shift_id = $currentShift->id;
            }
            
            $purchaseReturn->save();
            
            // Create return items for all purchase items
            foreach ($purchase->items as $item) {
                $returnItem = new PurchaseReturnItem();
                $returnItem->purchase_return_id = $purchaseReturn->id;
                $returnItem->product_id = $item->product_id;
                $returnItem->unit_id = $item->unit_id;
                $returnItem->quantity = $item->quantity;
                $returnItem->purchase_price = $item->purchase_price;
                $returnItem->reason = $request->reason ?? 'مرتجع كامل';
                $returnItem->save();
                
                // Record stock movement for the returned items
                StockMovement::recordMovement([
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'quantity' => $item->quantity,
                    'movement_type' => 'out', // Decreasing stock for returns
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->id,
                    'employee_id' => $request->employee_id ?? auth()->id(),
                    'notes' => 'مرتجع كامل للفاتورة رقم: ' . $purchase->invoice_number
                ]);
                
                // Adjust the product stock quantity
                $product = Product::find($item->product_id);
                $productUnit = ProductUnit::where('product_id', $item->product_id)
                    ->where('unit_id', $item->unit_id)
                    ->first();
                
                if ($productUnit) {
                    $quantityInMainUnit = $item->quantity * $productUnit->conversion_factor;
                    $product->stock_quantity -= $quantityInMainUnit;
                    $product->save();
                }
            }
            
            // Update supplier balance
            $purchaseReturn->updateSupplierBalance();
            
            // Update the original purchase invoice
            if ($purchase) {
                // For full returns, set the purchase total to zero
                $purchase->total_amount = 0;
                $purchase->remaining_amount = $purchase->total_amount - $purchase->paid_amount;
                $purchase->status = 'paid'; // Since there's nothing left to pay
                $purchase->save();
                
                // If there's a supplier, update their amounts
                if ($purchase->supplier) {
                    $purchase->supplier->updateAmounts();
                }
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Purchase fully returned successfully',
                'purchase_return' => $purchaseReturn->fresh(['supplier', 'employee', 'purchase', 'items.product', 'items.unit'])
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error returning full purchase: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error returning full purchase',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get purchase details for return
     */
    public function getPurchaseDetailsForReturn($purchaseId)
    {
        try {
            $purchase = Purchase::with(['supplier', 'items.product', 'items.unit'])
                ->findOrFail($purchaseId);
                
            return response()->json([
                'success' => true,
                'purchase' => $purchase
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching purchase details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching purchase details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 