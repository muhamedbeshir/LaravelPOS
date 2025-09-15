<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض قائمة الألوان
     */
    public function index()
    {
        $colors = Color::orderBy('name')->get();
        return view('colors.index', compact('colors'));
    }

    /**
     * عرض نموذج إنشاء لون جديد
     */
    public function create()
    {
        return view('colors.create');
    }

    /**
     * حفظ لون جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:colors',
        ]);

        $color = Color::create([
            'name' => $request->name,
        ]);

        return redirect()->route('colors.index')->with('success', 'تم إضافة اللون بنجاح.');
    }

    /**
     * عرض نموذج تعديل لون
     */
    public function edit(Color $color)
    {
        return view('colors.edit', compact('color'));
    }

    /**
     * تحديث لون
     */
    public function update(Request $request, Color $color)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:colors,name,' . $color->id,
        ]);

        $color->update([
            'name' => $request->name,
        ]);

        return redirect()->route('colors.index')->with('success', 'تم تحديث اللون بنجاح.');
    }

    /**
     * حذف لون
     */
    public function destroy(Color $color)
    {
        // التحقق من استخدام اللون في المنتجات
        if ($color->productVariants()->count() > 0) {
            return redirect()->route('colors.index')->with('error', 'لا يمكن حذف اللون لأنه مستخدم في منتجات.');
        }

        $color->delete();
        return redirect()->route('colors.index')->with('success', 'تم حذف اللون بنجاح.');
    }

    /**
     * الحصول على قائمة الألوان بتنسيق JSON (للاستخدام في واجهة المستخدم)
     */
    public function getColors()
    {
        $colors = Color::orderBy('name')->get();
        return response()->json($colors);
    }
} 