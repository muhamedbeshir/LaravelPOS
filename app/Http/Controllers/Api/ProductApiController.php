<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductUnit;
use App\Models\ProductPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Services\CacheService;

class ProductApiController extends Controller
{
    /**
     * Get all products with optional filtering
     */
    public function getAllProducts(Request $request)
    {
        try {
            // Build filter array for the cache service
            $filters = [
                'category_id' => $request->input('category_id'),
                'search' => $request->input('search'),
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
                'stock_status' => $request->input('stock_status')
            ];
            
            // Remove null/empty values from filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            // Get sort parameters
            $sortBy = $request->input('order_by', 'name');
            $sortDirection = $request->input('order_direction', 'asc');
            
            // Validate sort field
            if (!in_array($sortBy, ['name', 'id', 'created_at', 'stock_quantity'])) {
                $sortBy = 'name';
            }
            
            // Get per_page parameter
            $perPage = (int)$request->input('per_page', 15);
            
            // Use cached product listing
            $result = CacheService::getFilteredProducts($filters, $perPage, $sortBy, $sortDirection);
            
            return response()->json([
                'success' => true,
                'products' => $result['products'],
                'pagination' => $result['pagination']
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific product with its details
     */
    public function getProduct(Request $request, $id)
    {
        try {
            // Use cache service to get product with details
            $product = CacheService::getProductWithDetails($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a new product
     */
    public function storeProduct(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'barcode' => 'nullable|string|unique:products,barcode',
                'has_serial' => 'nullable|boolean',
                'serial_number' => 'required_if:has_serial,1|nullable|string|unique:products,serial_number',
                'alert_quantity' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'units' => 'required|array|min:1',
                'units.*.unit_id' => 'required|exists:units,id',
                'units.*.barcode' => 'nullable|string|unique:product_units,barcode',
                'units.*.main_price' => 'required|numeric|min:0',
                'units.*.app_price' => 'nullable|numeric|min:0',
                'units.*.other_price' => 'nullable|numeric|min:0',
                'main_unit_index' => 'required|numeric|min:0'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Prepare product data
            $productData = [
                'name' => $request->name,
                'category_id' => $request->category_id,
                'barcode' => $request->barcode,
                'has_serial' => $request->boolean('has_serial', false),
                'serial_number' => $request->serial_number,
                'alert_quantity' => $request->alert_quantity ?? 0,
                'stock_quantity' => 0,
                'is_active' => true
            ];
            
            // Handle image upload if present
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                
                // Ensure directory exists
                if (!Storage::disk('public')->exists('products')) {
                    Storage::disk('public')->makeDirectory('products');
                }
                
                // Save image
                $path = $image->storeAs('products', $filename, 'public');
                if (!$path) {
                    throw new \Exception('Failed to save image');
                }
                
                $productData['image'] = $filename;
            }
            
            // Create product
            $product = Product::create($productData);
            
            // Add product units
            $units = $request->input('units');
            $mainUnitIndex = $request->input('main_unit_index');
            
            foreach ($units as $index => $unitData) {
                $productUnit = $product->units()->create([
                    'unit_id' => $unitData['unit_id'],
                    'barcode' => $unitData['barcode'] ?? null,
                    'main_price' => $unitData['main_price'],
                    'app_price' => $unitData['app_price'] ?? null,
                    'other_price' => $unitData['other_price'] ?? null,
                    'is_main_unit' => ($index == $mainUnitIndex),
                    'is_active' => true
                ]);
                
                // Update main unit for product
                if ($index == $mainUnitIndex) {
                    $product->update(['main_unit_id' => $unitData['unit_id']]);
                }
            }
            
            DB::commit();
            
            // Clear product caches
            CacheService::clearProductCaches();
            
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product->fresh(['category', 'units', 'mainUnit'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete image if there was an error
            if (isset($filename) && Storage::disk('public')->exists('products/' . $filename)) {
                Storage::disk('public')->delete('products/' . $filename);
            }
            
            Log::error('Error creating product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update an existing product
     */
    public function updateProduct(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Find product
            $product = Product::findOrFail($id);
            
            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
                'has_serial' => 'nullable|boolean',
                'serial_number' => 'required_if:has_serial,1|nullable|string|unique:products,serial_number,' . $product->id,
                'alert_quantity' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'units' => 'required|array|min:1',
                'units.*.id' => 'nullable|exists:product_units,id',
                'units.*.unit_id' => 'required|exists:units,id',
                'units.*.barcode' => 'nullable|string',
                'units.*.main_price' => 'required|numeric|min:0',
                'units.*.app_price' => 'nullable|numeric|min:0',
                'units.*.other_price' => 'nullable|numeric|min:0',
                'main_unit_index' => 'required|numeric|min:0'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update product data
            $productData = [
                'name' => $request->name,
                'category_id' => $request->category_id,
                'barcode' => $request->barcode,
                'has_serial' => $request->boolean('has_serial', false),
                'serial_number' => $request->serial_number,
                'alert_quantity' => $request->alert_quantity ?? 0,
            ];
            
            // Handle image upload if present
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
                    Storage::disk('public')->delete('products/' . $product->image);
                }
                
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                
                // Ensure directory exists
                if (!Storage::disk('public')->exists('products')) {
                    Storage::disk('public')->makeDirectory('products');
                }
                
                // Save new image
                $path = $image->storeAs('products', $filename, 'public');
                if (!$path) {
                    throw new \Exception('Failed to save image');
                }
                
                $productData['image'] = $filename;
            }
            
            // Update product
            $product->update($productData);
            
            // Update units
            $units = $request->input('units');
            $mainUnitIndex = $request->input('main_unit_index');
            
            // Get existing unit IDs
            $existingUnitIds = [];
            
            foreach ($units as $index => $unitData) {
                if (isset($unitData['id'])) {
                    // Update existing unit
                    $productUnit = ProductUnit::findOrFail($unitData['id']);
                    $existingUnitIds[] = $productUnit->id;
                    
                    // Check for price changes and record in history
                    if ($productUnit->main_price != $unitData['main_price']) {
                        ProductPriceHistory::create([
                            'product_id' => $product->id,
                            'product_unit_id' => $productUnit->id,
                            'old_price' => $productUnit->main_price,
                            'new_price' => $unitData['main_price'],
                            'change_type' => 'manual_update'
                        ]);
                    }
                    
                    $productUnit->update([
                        'unit_id' => $unitData['unit_id'],
                        'barcode' => $unitData['barcode'] ?? null,
                        'main_price' => $unitData['main_price'],
                        'app_price' => $unitData['app_price'] ?? null,
                        'other_price' => $unitData['other_price'] ?? null,
                        'is_main_unit' => ($index == $mainUnitIndex)
                    ]);
                } else {
                    // Create new unit
                    $productUnit = $product->units()->create([
                        'unit_id' => $unitData['unit_id'],
                        'barcode' => $unitData['barcode'] ?? null,
                        'main_price' => $unitData['main_price'],
                        'app_price' => $unitData['app_price'] ?? null,
                        'other_price' => $unitData['other_price'] ?? null,
                        'is_main_unit' => ($index == $mainUnitIndex),
                        'is_active' => true
                    ]);
                    
                    $existingUnitIds[] = $productUnit->id;
                }
                
                // Update main unit for product
                if ($index == $mainUnitIndex) {
                    $product->update(['main_unit_id' => $unitData['unit_id']]);
                }
            }
            
            // Delete units not in the request
            ProductUnit::where('product_id', $product->id)
                ->whereNotIn('id', $existingUnitIds)
                ->delete();
            
            DB::commit();
            
            // Clear product caches
            CacheService::clearProductCaches();
            CacheService::clearCacheKey('product_' . $id);
            
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product->fresh(['category', 'units', 'mainUnit'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a product
     */
    public function deleteProduct($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Check if product has related data
            if ($product->invoiceItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product with related invoice items'
                ], 422);
            }
            
            DB::beginTransaction();
            
            // Delete product units
            $product->units()->delete();
            
            // Delete product image if exists
            if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
                Storage::disk('public')->delete('products/' . $product->image);
            }
            
            // Delete product
            $product->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle product active status
     */
    public function toggleProductStatus($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->is_active = !$product->is_active;
            $product->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Product status updated successfully',
                'is_active' => $product->is_active
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error toggling product status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error toggling product status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get price history for a product
     */
    public function getPriceHistory($id)
    {
        try {
            $product = Product::findOrFail($id);
            $priceHistory = ProductPriceHistory::with(['productUnit.unit'])
                ->where('product_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'product' => $product,
                'price_history' => $priceHistory
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching price history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching price history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $term = $request->get('q');

        if (empty($term)) {
            return response()->json(['data' => []]);
        }

        $products = Product::where('name', 'LIKE', "%{$term}%")
            ->orWhere('sku', 'LIKE', "%{$term}%")
            ->limit(10)
            ->get(['id', 'name as text']);

        return response()->json(['data' => $products]);
    }

    public function searchForSelect2(Request $request)
    {
        $term = $request->input('q', '');
        $page = $request->input('page', 1);

        $query = Product::query();

        if (!empty($term)) {
            $query->where(function($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('barcode', 'LIKE', "%{$term}%");
            });
        }

        $products = $query->select(['id', 'name as text'])
                             ->paginate(10, ['*'], 'page', $page);

        return response()->json([
            'results' => $products->items(),
            'pagination' => [
                'more' => $products->hasMorePages()
            ]
        ]);
    }

    /**
     * Find a single product by a given term (SKU, barcode, or name).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findOneByTerm(Request $request)
    {
        $term = $request->input('term');
        if (!$term) {
            return response()->json(['success' => false, 'message' => 'Term is required.'], 400);
        }

        // Prioritize exact match on SKU or a dedicated barcode field
        // Assumes a 'barcode' column exists. If not, remove it from the query.
        $product = Product::where('barcode', $term)->first();

        // If no exact match, try a like search on name, but only if it returns a single, unambiguous result
        if (!$product) {
            $products = Product::where('name', 'LIKE', "%{$term}%")->get();
            if ($products->count() === 1) {
                $product = $products->first();
            }
        }
        
        // If we have a product, return its ID
        if ($product) {
            return response()->json(['success' => true, 'product_id' => $product->id]);
        }

        // Otherwise, not found
        return response()->json(['success' => false, 'message' => 'Product not found.'], 200);
    }

    /**
     * Get units for a specific product.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductUnits(Product $product)
    {
        try {
            $units = $product->units()->with('unit')->get()->map(function ($productUnit) use ($product) {
                return [
                    'id' => $productUnit->id,
                    'unit_id' => $productUnit->unit->id,
                    'name' => $productUnit->unit->name,
                    'is_main_unit' => $productUnit->is_main_unit,
                ];
            });

            return response()->json($units);
        } catch (\Exception $e) {
            Log::error("Error fetching units for product {$product->id}: " . $e->getMessage());
            return response()->json(['error' => 'Could not fetch product units.'], 500);
        }
    }

    /**
     * Search for products by name or barcode.
     * Used for sales returns and other quick lookups.
     */
 
} 