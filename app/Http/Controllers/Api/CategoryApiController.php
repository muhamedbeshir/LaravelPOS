<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CategoryApiController extends Controller
{
    /**
     * Get all categories
     */
    public function getAllCategories(Request $request)
    {
        try {
            $query = Category::query();
            
            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            // Search by name
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('name', 'like', "%{$search}%");
            }
            
            // Sort by name
            $query->orderBy('name');
            
            $categories = $query->get();
            
            // Add products count
            $categories->each(function ($category) {
                $category->products_count = $category->products()->count();
            });
            
            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific category with its products
     */
    public function getCategory($id)
    {
        try {
            $category = Category::findOrFail($id);
            $products = $category->products()->with('mainUnit')->orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'category' => $category,
                'products' => $products,
                'products_count' => $products->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Category not found or error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Create a new category
     */
    public function storeCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories',
                'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $data = $request->except('image');
            $data['is_active'] = true;
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                
                // Ensure directory exists
                if (!Storage::disk('public')->exists('categories')) {
                    Storage::disk('public')->makeDirectory('categories');
                }
                
                // Store image
                $path = $image->storeAs('categories', $filename, 'public');
                if (!$path) {
                    throw new \Exception('Failed to save image');
                }
                
                $data['image'] = $filename;
            }
            
            $category = Category::create($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete image if there was an error
            if (isset($filename) && Storage::disk('public')->exists('categories/' . $filename)) {
                Storage::disk('public')->delete('categories/' . $filename);
            }
            
            Log::error('Error creating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a category
     */
    public function updateCategory(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
                'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $data = $request->except('image');
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($category->image && Storage::disk('public')->exists('categories/' . $category->image)) {
                    Storage::disk('public')->delete('categories/' . $category->image);
                }
                
                // Upload new image
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                
                // Ensure directory exists
                if (!Storage::disk('public')->exists('categories')) {
                    Storage::disk('public')->makeDirectory('categories');
                }
                
                // Store image
                $path = $image->storeAs('categories', $filename, 'public');
                if (!$path) {
                    throw new \Exception('Failed to save image');
                }
                
                $data['image'] = $filename;
            }
            
            $category->update($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'category' => $category->fresh()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete image if there was an error
            if (isset($filename) && Storage::disk('public')->exists('categories/' . $filename)) {
                Storage::disk('public')->delete('categories/' . $filename);
            }
            
            Log::error('Error updating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a category
     */
    public function deleteCategory($id)
    {
        try {
            DB::beginTransaction();
            
            $category = Category::findOrFail($id);
            
            // Check if category has products
            if ($category->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with products'
                ], 422);
            }
            
            // Delete image if exists
            if ($category->image && Storage::disk('public')->exists('categories/' . $category->image)) {
                Storage::disk('public')->delete('categories/' . $category->image);
            }
            
            $category->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle category active status
     */
    public function toggleCategoryStatus($id)
    {
        try {
            DB::beginTransaction();
            
            $category = Category::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Category status updated successfully',
                'is_active' => $category->is_active
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error toggling category status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error toggling category status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get category statistics
     */
    public function getCategoryStatistics()
    {
        try {
            $categories = Category::all();
            
            $statistics = $categories->map(function ($category) {
                $productsCount = $category->products()->count();
                $activeProductsCount = $category->products()->where('is_active', true)->count();
                $totalStock = $category->products()->sum('stock_quantity');
                $lowStockCount = $category->products()
                    ->whereRaw('stock_quantity <= alert_quantity')
                    ->where('alert_quantity', '>', 0)
                    ->count();
                
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'is_active' => $category->is_active,
                    'products_count' => $productsCount,
                    'active_products_count' => $activeProductsCount,
                    'total_stock' => $totalStock,
                    'low_stock_count' => $lowStockCount
                ];
            });
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching category statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching category statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 