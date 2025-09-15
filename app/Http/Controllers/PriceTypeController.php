<?php

namespace App\Http\Controllers;

use App\Models\PriceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PriceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $priceTypes = PriceType::orderBy('sort_order')->get();
        return view('price-types.index', compact('priceTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('price-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'sort_order' => 'required|integer|min:1',
            'is_default' => 'nullable'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Always generate a new code from the name, ignoring any provided code
        $code = $this->generateUniqueCode($request->name);

        // التحقق من صحة الكود وتفرده
        $codeValidator = Validator::make(['code' => $code], [
            'code' => 'required|string|unique:price_types,code|max:50|regex:/^[a-z0-9_]+$/'
        ]);

        if ($codeValidator->fails()) {
            return redirect()->back()
                ->withErrors($codeValidator)
                ->withInput()
                ->with('error', 'فشل إنشاء كود فريد من الاسم، الرجاء المحاولة مرة أخرى');
        }

        // إذا كان نوع السعر الجديد افتراضي، يجب إلغاء الإعداد الافتراضي من الأنواع الأخرى
        if ($request->has('is_default')) {
            PriceType::query()->update(['is_default' => false]);
        }

        PriceType::create([
            'name' => $request->name,
            'code' => $code,
            'sort_order' => $request->sort_order,
            'is_default' => $request->has('is_default'),
            'is_active' => true
        ]);

        return redirect()->route('price-types.index')
            ->with('success', 'تم إنشاء نوع السعر بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $priceType = PriceType::findOrFail($id);
        
        return response()->json([
            'id' => $priceType->id,
            'name' => $priceType->name,
            'code' => $priceType->code,
            'is_default' => $priceType->is_default,
            'is_active' => $priceType->is_active
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PriceType $priceType)
    {
        return view('price-types.edit', compact('priceType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PriceType $priceType)
    {
        // Debug - log the incoming request data
        \Log::info('PriceType update request data:', [
            'request_all' => $request->all(),
            'price_type_id' => $priceType->id,
            'price_type_before' => $priceType->toArray()
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:1',
            'is_default' => 'nullable',
            'is_active' => 'nullable'
        ]);

        if ($validator->fails()) {
            \Log::warning('PriceType validation failed:', ['errors' => $validator->errors()->toArray()]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Always generate a new code if the name has changed
        $code = $priceType->code;
        if ($request->name !== $priceType->name) {
            $code = $this->generateUniqueCode($request->name, $priceType->id);
        }

        // التحقق من صحة الكود وتفرده
        $codeValidator = Validator::make(['code' => $code], [
            'code' => 'required|string|max:50|regex:/^[a-z0-9_]+$/|unique:price_types,code,' . $priceType->id
        ]);

        if ($codeValidator->fails()) {
            \Log::warning('PriceType code validation failed:', ['errors' => $codeValidator->errors()->toArray()]);
            return redirect()->back()
                ->withErrors($codeValidator)
                ->withInput()
                ->with('error', 'فشل إنشاء كود فريد من الاسم، الرجاء المحاولة مرة أخرى');
        }

        // إذا كان نوع السعر الجديد افتراضي، يجب إلغاء الإعداد الافتراضي من الأنواع الأخرى
        if ($request->has('is_default')) {
            PriceType::where('id', '!=', $priceType->id)->update(['is_default' => false]);
        }
        
        // التأكد من وجود نوع سعر افتراضي واحد على الأقل
        if (!$request->has('is_default') && $priceType->is_default) {
            $otherDefaultExists = PriceType::where('id', '!=', $priceType->id)
                ->where('is_default', true)
                ->exists();
                
            if (!$otherDefaultExists) {
                \Log::warning('Cannot remove default status - no other default price type exists');
                return redirect()->back()
                    ->with('error', 'يجب أن يكون هناك نوع سعر افتراضي واحد على الأقل')
                    ->withInput();
            }
        }

        $updateData = [
            'name' => $request->name,
            'code' => $code,
            'sort_order' => $request->sort_order,
            'is_default' => $request->has('is_default'),
            'is_active' => $request->has('is_active')
        ];
        
        \Log::info('PriceType attempting to update with data:', ['update_data' => $updateData]);
        
        $priceType->update($updateData);
        
        \Log::info('PriceType after update:', ['price_type_after' => $priceType->fresh()->toArray()]);

        return redirect()->route('price-types.index')
            ->with('success', 'تم تحديث نوع السعر بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PriceType $priceType)
    {
        // التحقق مما إذا كان نوع السعر مستخدم
        $usageCount = $priceType->productUnitPrices()->count();
        
        if ($usageCount > 0) {
            return redirect()->back()
                ->with('error', 'لا يمكن حذف نوع السعر لأنه مستخدم في ' . $usageCount . ' سعر');
        }
        
        // التأكد من عدم حذف النوع الافتراضي إذا كان هو الوحيد
        if ($priceType->is_default) {
            $totalCount = PriceType::count();
            $defaultCount = PriceType::where('is_default', true)->count();
            
            if ($totalCount == 1 || $defaultCount == 1) {
                return redirect()->back()
                    ->with('error', 'لا يمكن حذف نوع السعر الافتراضي الوحيد');
            }
        }

        $priceType->delete();

        return redirect()->route('price-types.index')
            ->with('success', 'تم حذف نوع السعر بنجاح');
    }
    
    /**
     * Toggle the active status of the price type.
     */
    public function toggleActive(PriceType $priceType)
    {
        // لا يمكن تعطيل نوع السعر الافتراضي
        if ($priceType->is_default && $priceType->is_active) {
            return redirect()->back()
                ->with('error', 'لا يمكن تعطيل نوع السعر الافتراضي');
        }

        $priceType->update([
            'is_active' => !$priceType->is_active
        ]);

        return redirect()->back()
            ->with('success', 'تم تغيير حالة نوع السعر بنجاح');
    }

    /**
     * Get available price types for API.
     */
    public function getAvailablePriceTypes()
    {
        $priceTypes = PriceType::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'is_default']);
            
        return response()->json($priceTypes);
    }

    /**
     * توليد كود فريد مبني على الاسم
     */
    private function generateUniqueCode($name, $exceptId = null)
    {
        // تحويل الاسم إلى كود صالح
        $baseCode = Str::slug($name, '_');
        $baseCode = strtolower(preg_replace('/[^\w_]/', '', $baseCode));
        $baseCode = preg_replace('/_{2,}/', '_', $baseCode);
        
        // التأكد من أن الكود فريد
        $code = $baseCode;
        $i = 1;
        
        while (true) {
            $query = PriceType::where('code', $code);
            
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            
            $exists = $query->exists();
            
            if (!$exists) {
                break;
            }
            
            $code = $baseCode . '_' . $i;
            $i++;
        }
        
        return $code;
    }
}
