<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض قائمة المقاسات
     */
    public function index()
    {
        $sizes = Size::orderBy('name')->get();
        return view('sizes.index', compact('sizes'));
    }

    /**
     * عرض نموذج إنشاء مقاس جديد
     */
    public function create()
    {
        return view('sizes.create');
    }

    /**
     * حفظ مقاس جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sizes',
        ]);

        $size = Size::create([
            'name' => $request->name,
        ]);

        return redirect()->route('sizes.index')->with('success', 'تم إضافة المقاس بنجاح.');
    }

    /**
     * عرض نموذج تعديل مقاس
     */
    public function edit(Size $size)
    {
        return view('sizes.edit', compact('size'));
    }

    /**
     * تحديث مقاس
     */
    public function update(Request $request, Size $size)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name,' . $size->id,
        ]);

        $size->update([
            'name' => $request->name,
        ]);

        return redirect()->route('sizes.index')->with('success', 'تم تحديث المقاس بنجاح.');
    }

    /**
     * حذف مقاس
     */
    public function destroy(Size $size)
    {
        // التحقق من استخدام المقاس في المنتجات
        if ($size->productVariants()->count() > 0) {
            return redirect()->route('sizes.index')->with('error', 'لا يمكن حذف المقاس لأنه مستخدم في منتجات.');
        }

        $size->delete();
        return redirect()->route('sizes.index')->with('success', 'تم حذف المقاس بنجاح.');
    }

    /**
     * الحصول على قائمة المقاسات بتنسيق JSON (للاستخدام في واجهة المستخدم)
     */
    public function getSizes()
    {
        $sizes = Size::orderBy('name')->get();
        return response()->json($sizes);
    }
} 