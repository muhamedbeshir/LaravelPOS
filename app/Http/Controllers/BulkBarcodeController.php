<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PriceType;
use App\Models\Setting;
use App\Models\ProductUnitBarcode;
use Illuminate\Http\Request;

class BulkBarcodeController extends Controller
{
    public function index()
    {
        // Get all active price types for selection
        $priceTypes = PriceType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
            
        $settings = Setting::pluck('value', 'key')->all();
        
        return view('barcodes.bulk_print', compact('priceTypes', 'settings'));
    }
    
    public function getProducts(Request $request)
    {
        $query = Product::with(['category', 'units.unit', 'units.barcodes', 'units.prices.priceType'])
            ->where('is_active', true);
        
        // Filter by search term if provided
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhereHas('units.barcodes', function($q) use ($search) {
                      $q->where('barcode', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by category if provided
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        $products = $query->orderBy('name')
            ->limit(30)
            ->get();
            
        return response()->json([
            'products' => $products
        ]);
    }
    
    public function printLabels(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.barcode_id' => 'required|exists:product_unit_barcodes,id',
            'items.*.copies' => 'required|integer|min:1|max:100',
            'items.*.price_type_id' => 'required|exists:price_types,id',
            'barcode_type' => 'required|string',
            'is_preview' => 'nullable|boolean',
        ]);
        
        $productsToProcess = collect($validated['items']);
        $products_to_print = [];
        
        foreach ($productsToProcess as $item) {
            $product = Product::findOrFail($item['product_id']);
            $barcode = ProductUnitBarcode::with('productUnit.unit')->findOrFail($item['barcode_id']);
            $productUnit = $barcode->productUnit;
            $priceType = PriceType::findOrFail($item['price_type_id']);
            
            if (!$productUnit) {
                continue;
            }
            
            $price = $productUnit->prices()->where('price_type_id', $priceType->id)->first();
            
            $copies = $item['copies'];
            
            for ($i = 0; $i < $copies; $i++) {
                $products_to_print[] = [
                    'name' => $product->name,
                    'price' => $price ? $price->value : null,
                    'barcode_value' => $barcode->barcode,
                ];
            }
        }
        
        $data = [
            'products_to_print' => $products_to_print,
            'barcode_type' => $validated['barcode_type'] ?? 'C128',
            'print_settings' => Setting::pluck('value', 'key')->all(),
            'is_preview' => $validated['is_preview'] ?? false,
            'is_bulk' => true,
        ];
        
        return view('barcodes.bulk_print_labels', $data);
    }
} 