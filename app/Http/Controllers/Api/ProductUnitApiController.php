<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductUnitApiController extends Controller
{
    public function getPrices($id)
    {
        $productUnit = ProductUnit::findOrFail($id);
        
        $prices = $productUnit->prices->map(function($price) {
            return [
                'id' => $price->id,
                'name' => $price->priceType->name,
                'value' => $price->value,
                'is_default' => $price->priceType->is_default
            ];
        });
        
        return response()->json([
            'success' => true,
            'prices' => $prices
        ]);
    }
    
    /**
     * Get the last purchase price for a product unit
     * 
     * @param int $id The product unit ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLastPurchasePrice($id)
    {
        $productUnit = ProductUnit::findOrFail($id);
        
        // Get the last purchase item for this unit and product
        $lastPurchase = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.product_id', $productUnit->product_id)
            ->where('purchase_items.unit_id', $productUnit->unit_id)
            ->whereNull('purchases.deleted_at')
            ->orderBy('purchases.purchase_date', 'desc')
            ->select('purchase_items.purchase_price')
            ->first();
        
        return response()->json([
            'success' => true,
            'lastPurchasePrice' => $lastPurchase ? $lastPurchase->purchase_price : null
        ]);
    }
}
