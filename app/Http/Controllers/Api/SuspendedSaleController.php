<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuspendedSale;
use App\Models\SuspendedSaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SuspendedSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SuspendedSale::with(['customer:id,name', 'user:id,name'])
            ->select('id', 'reference_no', 'customer_id', 'user_id', 'total_amount', 'created_at')
            ->orderBy('created_at', 'desc');

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('reference_no', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                        $cq->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $suspendedSales = $query->paginate($request->input('per_page', 15));

        return response()->json($suspendedSales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'invoice_type' => 'required|string|in:cash,credit',
            'order_type' => 'required|string|in:takeaway,delivery',
            'price_type_code' => 'nullable|string|exists:price_types,code',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'delivery_employee_id' => 'nullable|exists:employees,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.sub_total' => 'required|numeric',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $suspendedSale = SuspendedSale::create([
                'reference_no' => 'SUS-' . Str::upper(Str::random(8)),
                'customer_id' => $request->customer_id,
                'user_id' => Auth::id(),
                'invoice_type' => $request->invoice_type,
                'order_type' => $request->order_type,
                'price_type_code' => $request->price_type_code,
                'discount_value' => $request->discount_value ?? 0,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'total_amount' => $request->total_amount,
                'paid_amount' => $request->paid_amount ?? 0,
                'notes' => $request->notes,
                'delivery_employee_id' => $request->delivery_employee_id,
            ]);

            foreach ($request->items as $itemData) {
                // Find the correct unit_id from the product_units table
                $productUnit = \App\Models\ProductUnit::find($itemData['unit_id']);
                $correctUnitId = $productUnit ? $productUnit->unit_id : null;

                if (!$correctUnitId) {
                    // Handle case where the product_unit is not found, though validation should prevent this.
                    throw new \Exception("Invalid unit provided for product ID: " . $itemData['product_id']);
                }

                $suspendedSale->items()->create([
                    'product_id' => $itemData['product_id'],
                    'unit_id' => $correctUnitId, // Use the corrected unit_id
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_value' => $itemData['discount_value'] ?? 0,
                    'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                    'sub_total' => $itemData['sub_total'],
                    'cost_price' => $itemData['cost_price'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم تعليق الفاتورة بنجاح.', 'suspended_sale' => $suspendedSale->load('items')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تعليق الفاتورة: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SuspendedSale $suspendedSale)
    {
        $suspendedSale->load([
            'items.product:id,name,image', // Main product details
            'items.product.units', // Load all ProductUnits for each product
            'items.product.units.unit:id,name', // For each ProductUnit, load its base Unit details
            'items.unit:id,name', // The specific unit of the suspended sale item
            'customer:id,name,phone,address,payment_type,credit_balance,credit_limit,is_unlimited_credit',
            'user:id,name',
            'deliveryEmployee:id,name',
            'priceType:code,name'
        ]);
        return response()->json(['success' => true, 'suspended_sale' => $suspendedSale]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SuspendedSale $suspendedSale)
    {
        DB::beginTransaction();
        try {
            $suspendedSale->items()->delete();
            $suspendedSale->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم حذف الفاتورة المعلقة بنجاح.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء حذف الفاتورة المعلقة: ' . $e->getMessage()], 500);
        }
    }
}
