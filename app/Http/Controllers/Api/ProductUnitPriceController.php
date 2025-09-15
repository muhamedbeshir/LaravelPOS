<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductUnit;
use App\Models\ProductUnitPrice;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

class ProductUnitPriceController extends Controller
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
     * Display the specified resource.
     */
    public function show(string $id)
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
     * Display a listing of all prices for a specific product unit.
     *
     * @param int $productUnitId
     * @return \Illuminate\Http\Response
     */
    public function getPrices($productUnitId)
    {
        try {
            // التحقق من وجود وحدة المنتج
            $productUnit = ProductUnit::with(['unit', 'prices.priceType'])->findOrFail($productUnitId);
            
            // تحضير مصفوفة الأسعار للاستجابة
            $prices = $productUnit->prices->map(function($price) {
                return [
                    'id' => $price->id,
                    'price_type_id' => $price->price_type_id,
                    'price_type_name' => $price->priceType->name,
                    'price_type_code' => $price->priceType->code,
                    'value' => $price->value,
                    'is_default' => $price->priceType->is_default
                ];
            });
            
            return response()->json([
                'success' => true,
                'prices' => $prices,
                'product_unit' => [
                    'id' => $productUnit->id,
                    'product_id' => $productUnit->product_id,
                    'unit_id' => $productUnit->unit_id,
                    'unit_name' => $productUnit->unit->name,
                    'is_main_unit' => $productUnit->is_main_unit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب أسعار وحدة المنتج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على سعر آخر عملية شراء للوحدة
     *
     * @param int $productUnitId معرف وحدة المنتج
     * @return \Illuminate\Http\Response
     */
    public function getLastPurchasePrice($productUnitId)
    {
        try {
            // التحقق من وجود وحدة المنتج
            $productUnit = ProductUnit::with('product')->findOrFail($productUnitId);
            
            // البحث عن آخر عملية شراء لهذه الوحدة
            $lastPurchase = PurchaseItem::where('unit_id', $productUnit->unit_id)
                ->where('product_id', $productUnit->product_id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            $price = null;
            if ($lastPurchase) {
                $price = $lastPurchase->purchase_price;
            } else {
                // Use the cost from the product_unit if available
                $price = $productUnit->cost;
            }

            // The price can still be null here if cost is not set.
            // The frontend needs to handle `null`.
            return response()->json([
                'success' => true,
                'lastPurchasePrice' => $price, // Could be a value, or null
                'lastPurchaseDate' => $lastPurchase ? $lastPurchase->created_at->format('Y-m-d') : null,
                'message' => $price === null ? 'لا توجد عمليات شراء سابقة أو تكلفة محددة.' : ''
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء استرجاع سعر آخر شراء: ' . $e->getMessage()
            ], 500);
        }
    }
}
