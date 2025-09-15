<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    protected static array $middlewares = [
        'auth',
        'permission:view-categories' => ['only' => ['index', 'show']],
        'permission:create-categories' => ['only' => ['create', 'store']],
        'permission:edit-categories' => ['only' => ['edit', 'update', 'toggleActive']],
        'permission:delete-categories' => ['only' => ['destroy']],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories',
                'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                if ($request->ajax() || $request->has('ajax') || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => 'فشل التحقق من البيانات',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = $request->except('image');

            // معالجة الصورة
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                
                // التأكد من وجود المجلد
                if (!Storage::disk('public')->exists('categories')) {
                    Storage::disk('public')->makeDirectory('categories');
                }
                
                // حفظ الصورة
                $path = $request->file('image')->storeAs('categories', $filename, 'public');
                if (!$path) {
                    throw new \Exception('فشل في حفظ الصورة');
                }
                
                $data['image'] = $filename;
            }

            $category = Category::create($data);

            DB::commit();
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->has('ajax') || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إضافة المجموعة بنجاح',
                    'category' => $category
                ]);
            }
            
            return redirect()->route('categories.index')
                ->with('success', 'تم إضافة المجموعة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating category: ' . $e->getMessage());
            
            if (isset($filename) && Storage::disk('public')->exists('categories/' . $filename)) {
                Storage::disk('public')->delete('categories/' . $filename);
            }
            
            // Check if this is an AJAX request
            if ($request->ajax() || $request->has('ajax') || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إضافة المجموعة: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إضافة المجموعة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
                'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = $request->except('image');

            // معالجة الصورة
            if ($request->hasFile('image')) {
                // حذف الصورة القديمة
                if ($category->image && Storage::disk('public')->exists('categories/' . $category->image)) {
                    Storage::disk('public')->delete('categories/' . $category->image);
                }

                // التأكد من وجود المجلد
                if (!Storage::disk('public')->exists('categories')) {
                    Storage::disk('public')->makeDirectory('categories');
                }

                // حفظ الصورة الجديدة
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $request->file('image')->storeAs('categories', $filename, 'public');
                
                if (!$path) {
                    throw new \Exception('فشل في حفظ الصورة');
                }

                $data['image'] = $filename;
            }

            $category->update($data);

            DB::commit();
            return redirect()->route('categories.index')
                ->with('success', 'تم تحديث المجموعة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            if (isset($filename) && Storage::disk('public')->exists('categories/' . $filename)) {
                Storage::disk('public')->delete('categories/' . $filename);
            }
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث المجموعة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {
            DB::beginTransaction();

            // حذف الصورة إذا وجدت
            if ($category->image) {
                Storage::delete('public/categories/' . $category->image);
            }

            $category->delete();

            DB::commit();
            return redirect()->route('categories.index')
                ->with('success', 'تم حذف المجموعة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف المجموعة: ' . $e->getMessage());
        }
    }

    public function toggleActive(Category $category)
    {
        try {
            $category->is_active = !$category->is_active;
            $category->save();

            return redirect()->route('categories.index')
                ->with('success', 'تم تحديث حالة المجموعة بنجاح');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث حالة المجموعة: ' . $e->getMessage());
        }
    }
}
