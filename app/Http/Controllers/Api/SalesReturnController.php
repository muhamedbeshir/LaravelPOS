<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\ReturnItem;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Support\Facades\Validator;

class SalesReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Handles returning a single item directly.
     * Expects: product_id, quantity, unit_price, unit_id (optional), notes (optional), restock_items (optional)
     */
    public function returnByItem(Request $request): JsonResponse
    {
        // Gate::authorize('manage-sales-returns'); // Commented out to remove auth check

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'restock_items' => 'sometimes|boolean',
        ]);

        $user = Auth::user() ?? \App\Models\User::first();
        $employeeId = $user->employee_id ?? null;
        
        // Get any open shift instead of requiring the current user to be in a shift
        $currentShift = Shift::where('is_closed', false)->latest()->first();

        if (!$currentShift) {
            // For testing purposes, let's bypass the shift check
            // In production, you would want to require an open shift
            $currentShift = Shift::latest()->first();
            
            if (!$currentShift) {
                return response()->json(['success' => false, 'message' => 'لا توجد وردية مفتوحة حالياً.'], 400);
            }
        }

        $shouldRestock = filter_var($validated['restock_items'] ?? true, FILTER_VALIDATE_BOOLEAN);

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($validated['product_id']);
            
            // Get the unit from units table
            $unit = $validated['unit_id'] ? Unit::find($validated['unit_id']) : $product->mainUnitRelation;
            $unitIdForReturn = $unit ? $unit->id : ($product->main_unit_id ?? null);

            if (!$unitIdForReturn) {
                throw new \Exception("لا يمكن تحديد وحدة الإرجاع للمنتج المحدد.");
            }

            // Find the corresponding product_unit_id for this product and unit combination
            $productUnit = ProductUnit::where('product_id', $product->id)
                ->where('unit_id', $unitIdForReturn)
                ->first();

            if (!$productUnit) {
                throw new \Exception("لا يمكن العثور على وحدة المنتج المطابقة للمنتج والوحدة المحددين.");
            }

            $totalReturnedAmount = $validated['quantity'] * $validated['unit_price'];

            $salesReturn = SalesReturn::create([
                'invoice_id' => null,
                'user_id' => $user->id,
                'shift_id' => $currentShift->id,
                'return_date' => now(),
                'return_type' => 'item',
                'total_returned_amount' => $totalReturnedAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Use product_unit_id in return_items table
            ReturnItem::create([
                'sales_return_id' => $salesReturn->id,
                'product_id' => $product->id,
                'unit_id' => $productUnit->id, // Use product_unit_id instead of unit_id
                'quantity_returned' => $validated['quantity'],
                'unit_price_returned' => $validated['unit_price'],
                'sub_total_returned' => $totalReturnedAmount,
            ]);

            if ($shouldRestock) {
                StockMovement::recordMovement([
                    'product_id' => $product->id,
                    'unit_id' => $unitIdForReturn,
                    'quantity' => $validated['quantity'],
                    'movement_type' => 'in',
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $salesReturn->id,
                    'employee_id' => $employeeId,
                    'notes' => "إرجاع صنف مباشر: " . ($validated['notes'] ?? 'بدون ملاحظات')
                ]);
            }

            $currentShift->returns_amount = (float)($currentShift->returns_amount ?? 0) + $totalReturnedAmount;
            $currentShift->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم إرجاع الصنف بنجاح.', 'sales_return_id' => $salesReturn->id]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Error in returnByItem: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء معالجة طلب الإرجاع: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handles returning a full invoice.
     * Expects: invoice_id, notes (optional), restock_items (optional)
     */
    public function returnFullInvoice(Request $request): JsonResponse
    {
        // Gate::authorize('manage-sales-returns'); // Commented out to remove auth check

        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'notes' => 'nullable|string|max:1000',
            'restock_items' => 'sometimes|boolean',
        ]);

        $user = Auth::user() ?? \App\Models\User::first();
        $employeeId = $user->employee_id ?? null;
        
        // Get any open shift instead of requiring the current user to be in a shift
        $currentShift = Shift::where('is_closed', false)->latest()->first();

        if (!$currentShift) {
            // For testing purposes, let's bypass the shift check
            // In production, you would want to require an open shift
            $currentShift = Shift::latest()->first();
            
            if (!$currentShift) {
                return response()->json(['success' => false, 'message' => 'لا توجد وردية مفتوحة حالياً.'], 400);
            }
        }

        $shouldRestock = filter_var($validated['restock_items'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $invoice = Invoice::with(['items.product', 'items.unit'])->find($validated['invoice_id']);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'الفاتورة الأصلية غير موجودة.'], 404);
        }

        $existingFullReturn = SalesReturn::where('invoice_id', $invoice->id)
                                        ->where('return_type', 'full_invoice')
                                        ->exists();
        if ($existingFullReturn) {
            return response()->json(['success' => false, 'message' => 'هذه الفاتورة تم إرجاعها بالكامل مسبقاً.'], 400);
        }
        
        $allItemsPartiallyReturned = true;
        foreach ($invoice->items as $item) {
            $previouslyReturnedQuantity = ReturnItem::whereHas('salesReturn', function ($query) use ($invoice) {
                $query->where('invoice_id', $invoice->id);
            })
            ->where('product_id', $item->product_id)
            ->where('unit_id', $item->unit_id)
            ->sum('quantity_returned');
            
            if ($previouslyReturnedQuantity < $item->quantity) {
                $allItemsPartiallyReturned = false;
                break;
            }
        }
        
        if ($allItemsPartiallyReturned) {
            return response()->json(['success' => false, 'message' => 'تم إرجاع جميع الأصناف من هذه الفاتورة مسبقاً.'], 400);
        }

        try {
            DB::beginTransaction();
            $totalReturnedAmount = 0;

            $salesReturn = SalesReturn::create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'shift_id' => $currentShift->id,
                'return_date' => now(),
                'return_type' => 'full_invoice',
                'total_returned_amount' => 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($invoice->items as $item) {
                $itemSubTotalReturned = $item->quantity * $item->unit_price;
                $totalReturnedAmount += $itemSubTotalReturned;

                // We need to use the correct unit_id for the return item
                // invoice_items.unit_id is already product_units.id
                $unitIdForReturn = $item->unit_id;

                ReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'product_id' => $item->product_id,
                    'unit_id' => $unitIdForReturn,
                    'quantity_returned' => $item->quantity,
                    'unit_price_returned' => $item->unit_price,
                    'sub_total_returned' => $itemSubTotalReturned,
                ]);

                if ($shouldRestock) {
                    StockMovement::recordMovement([
                        'product_id' => $item->product_id,
                        'unit_id' => $unitIdForReturn,
                        'quantity' => $item->quantity,
                        'movement_type' => 'in',
                        'reference_type' => SalesReturn::class,
                        'reference_id' => $salesReturn->id,
                        'employee_id' => $employeeId,
                        'notes' => "إرجاع كامل للفاتورة رقم: {$invoice->invoice_number} - الصنف: {$item->product->name}"
                    ]);
                }
            }

            $salesReturn->update(['total_returned_amount' => $totalReturnedAmount]);
            $currentShift->returns_amount = (float)($currentShift->returns_amount ?? 0) + $totalReturnedAmount;
            $currentShift->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم إرجاع الفاتورة بالكامل بنجاح.', 'sales_return_id' => $salesReturn->id]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Error in returnFullInvoice: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء معالجة طلب الإرجاع الكامل: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handles returning specific items from an invoice.
     * Expects: invoice_id, items (array of product_id, unit_id, quantity, unit_price), notes (optional), restock_items (optional)
     */
    public function returnPartialInvoice(Request $request): JsonResponse
    {
        // Gate::authorize('manage-sales-returns');

        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'notes' => 'nullable|string|max:1000',
            'restock_items' => 'sometimes|boolean',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.unit_id' => 'required|integer|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'user_id' => 'sometimes|integer|exists:users,id',
        ], [
            'invoice_id.required' => 'رقم الفاتورة مطلوب',
            'invoice_id.exists' => 'رقم الفاتورة غير صحيح',
            'items.required' => 'يجب تحديد الأصناف المرتجعة',
            'items.*.product_id.required' => 'رقم المنتج مطلوب',
            'items.*.product_id.exists' => 'رقم المنتج غير صحيح',
            'items.*.unit_id.required' => 'يجب تحديد وحدة المنتج (product_units.id) بشكل صحيح.',
            'items.*.unit_id.exists' => 'وحدة المنتج غير صحيحة',
            'items.*.quantity.required' => 'الكمية مطلوبة',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر',
            'items.*.unit_price.required' => 'يجب تحديد سعر الوحدة المرتجعة.',
            'items.*.unit_price.min' => 'سعر الوحدة يجب أن يكون أكبر أو يساوي صفر',
        ]);

        $user = Auth::user() ?? \App\Models\User::first();
        $employeeId = $user->employee_id ?? null;

        // Get current shift
        $currentShift = Shift::where('is_closed', false)->latest()->first();
        
        if (!$currentShift) {
            return response()->json(['success' => false, 'message' => 'لا توجد وردية مفتوحة حالياً.'], 400);
        }

        $shouldRestock = filter_var($validated['restock_items'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $invoice = Invoice::with(['items.product', 'items.unit'])->find($validated['invoice_id']);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'الفاتورة الأصلية غير موجودة.'], 404);
        }

        try {
            DB::beginTransaction();
            
            $totalReturnedAmount = 0;
            $returnItemsData = [];
            
            foreach ($validated['items'] as $returnItemData) {
                // The unit_id from the request IS the product_unit_id
                $productUnitId = $returnItemData['unit_id'];
                
                $productUnit = ProductUnit::find($productUnitId);

                if (!$productUnit || $productUnit->product_id != $returnItemData['product_id']) {
                    throw new \Exception("وحدة المنتج غير صالحة للمنتج المحدد.");
                }

                // Check if the quantity being returned is valid
                $originalInvoiceItem = $invoice->items()
                                            ->where('product_id', $productUnit->product_id)
                                            ->where('unit_id', $productUnit->id) // Match on product_unit_id
                                            ->first();

                if (!$originalInvoiceItem) {
                    // This should not happen if the frontend sends correct data
                    throw new \Exception("الصنف الأصلي غير موجود في الفاتورة.");
                }

                $itemSubTotalReturned = $returnItemData['quantity'] * $returnItemData['unit_price'];
                $totalReturnedAmount += $itemSubTotalReturned;

                $returnItemsData[] = [
                    'product_id' => $originalInvoiceItem->product_id,
                    'unit_id' => $productUnit->id, // Store the product_unit_id
                    'quantity_returned' => $returnItemData['quantity'],
                    'unit_price_returned' => $returnItemData['unit_price'],
                    'sub_total_returned' => $itemSubTotalReturned,
                    'original_invoice_item_product_name' => $originalInvoiceItem->product->name,
                    'generic_unit_id' => $productUnit->unit_id // Store this for stock movement
                ];
            }

            if (empty($returnItemsData)) {
                 throw new \Exception("لم يتم تحديد أصناف صالحة للإرجاع.");
            }

            $salesReturn = SalesReturn::create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'shift_id' => $currentShift->id,
                'return_date' => now(),
                'return_type' => 'partial_invoice',
                'total_returned_amount' => $totalReturnedAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($returnItemsData as $data) {
                // Create the return item with product_units.id as unit_id
                ReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'invoice_item_id' => $data['original_invoice_item_product_name'],
                    'product_id' => $data['product_id'],
                    'unit_id' => $data['unit_id'], // This is product_units.id
                    'quantity_returned' => $data['quantity_returned'],
                    'unit_price_returned' => $data['unit_price_returned'],
                    'sub_total_returned' => $data['sub_total_returned']
                ]);

                if ($shouldRestock) {
                    StockMovement::recordMovement([
                        'product_id' => $data['product_id'],
                        'unit_id' => $data['generic_unit_id'],
                        'quantity' => $data['quantity_returned'],
                        'movement_type' => 'in',
                        'reference_type' => SalesReturn::class,
                        'reference_id' => $salesReturn->id,
                        'employee_id' => $employeeId,
                        'notes' => "إرجاع جزئي من الفاتورة رقم: {$invoice->invoice_number} - الصنف: {$data['original_invoice_item_product_name']}"
                    ]);
                }
            }
            
            $currentShift->returns_amount = (float)($currentShift->returns_amount ?? 0) + $totalReturnedAmount;
            $currentShift->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم إرجاع الأصناف المحددة بنجاح.', 'sales_return_id' => $salesReturn->id]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Error in returnPartialInvoice: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء معالجة طلب الإرجاع الجزئي: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle a direct return of a product without an original invoice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeDirectReturn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
            'restock_items' => 'required|boolean',
            'refund_method' => 'required|string|in:cash,store_credit',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $totalRefundAmount = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['price'];
            });

            // Create the main sales return record
            $salesReturn = SalesReturn::create([
                'user_id' => Auth::id() ?? 1,
                'invoice_id' => null, // No original invoice
                'shift_id' => Shift::where('is_closed', false)->latest()->first()->id ?? 1,
                'return_date' => now(),
                'return_type' => 'item', // Using 'item' instead of 'direct' as per enum
                'total_returned_amount' => $totalRefundAmount,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $itemData) {
                // The unit_id from the request is the product_units.id
                $productUnitId = $itemData['unit_id'];

                // Create the return item record
                $returnItem = ReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'product_id' => $itemData['product_id'],
                    'unit_id' => $productUnitId,
                    'quantity_returned' => $itemData['quantity'],
                    'unit_price_returned' => $itemData['price'],
                    'sub_total_returned' => $itemData['quantity'] * $itemData['price'],
                ]);

                // If restocking is enabled, create a stock movement
                if ($request->restock_items) {
                    StockMovement::recordMovement([
                        'product_id' => $returnItem->product_id,
                        'unit_id' => $returnItem->unit_id,
                        'quantity' => $returnItem->quantity_returned,
                        'movement_type' => 'in',
                        'reference_type' => SalesReturn::class,
                        'reference_id' => $salesReturn->id,
                        'notes' => 'إرجاع مباشر للمنتج: ' . ($salesReturn->notes ?? 'بدون ملاحظات'),
                    ]);
                }
            }
            
            // TODO: Handle refund logic (e.g., update shift totals for cash refund)

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تمت معالجة الإرجاع المباشر بنجاح!',
                'sales_return_id' => $salesReturn->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Direct Return Processing Failed: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'فشلت معالجة الإرجاع المباشر.'], 500);
        }
    }

    /**
     * عرض تفاصيل مرتجع محدد
     */
    public function show($id)
    {
        try {
            $salesReturn = SalesReturn::with([
                'items.product', 
                'items.unit', 
                'invoice.customer', 
                'user', 
                'shift'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'return' => $salesReturn
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في تحميل بيانات المرتجع'
            ], 404);
        }
    }
}
