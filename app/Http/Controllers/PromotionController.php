<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $promotions = Promotion::orderBy('created_at', 'desc')->get();
        return view('promotions.index', compact('promotions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::where('is_active', 1)->orderBy('name')->get();
        $customers = Customer::where('is_active', 1)->orderBy('name')->get();
        $categories = Category::where('is_active', 1)->orderBy('name')->get();
        $units = Unit::where('is_active', 1)->orderBy('name')->get();
        
        return view('promotions.create', compact('products', 'customers', 'categories', 'units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'promotion_type' => 'required|in:simple_discount,buy_x_get_y,spend_x_save_y,coupon_code',
            'applies_to' => 'required|in:product,category,all',
            'discount_value' => 'required_if:promotion_type,simple_discount|nullable|numeric|min:0',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        // إضافة تحقق خاص بعرض اشتر X واحصل على Y
        if ($request->promotion_type == 'buy_x_get_y') {
            $request->validate([
                'buy_product_id' => 'required|exists:products,id',
                'buy_quantity' => 'required|numeric|min:1',
                'buy_unit_id' => 'required|exists:units,id',
                'get_product_id' => 'required|exists:products,id',
                'get_quantity' => 'required|numeric|min:1',
                'get_unit_id' => 'required|exists:units,id',
            ]);
        }

        $data = $request->all();
        // Set is_active explicitly from checkbox
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        DB::beginTransaction();
        try {
            $promotion = Promotion::create($data);

            // معالجة عرض اشتر X واحصل على Y
            if ($request->promotion_type == 'buy_x_get_y') {
                // إضافة المنتج الذي يجب شراؤه (المنتج X)
                $promotion->products()->attach($request->buy_product_id, [
                    'unit_id' => $request->buy_unit_id,
                    'quantity' => $request->buy_quantity,
                    'product_type' => 'condition', // شرط للعرض
                ]);
                
                // إضافة المنتج الذي سيتم الحصول عليه مجانًا (المنتج Y)
                $promotion->products()->attach($request->get_product_id, [
                    'unit_id' => $request->get_unit_id,
                    'quantity' => $request->get_quantity,
                    'product_type' => 'reward', // مكافأة العرض
                ]);
            }
            // معالجة العروض الأخرى
            else if ($request->applies_to == 'product' && $request->has('products') && is_array($request->products)) {
                foreach ($request->products as $productId) {
                    $promotion->products()->attach($productId, [
                        'unit_id' => $request->unit_id ?? null,
                        'quantity' => 1,
                        'product_type' => 'condition',
                    ]);
                }
            }

            // Handle categories if applicable
            if ($request->applies_to == 'category' && $request->has('categories') && is_array($request->categories)) {
                // We'll need to create a promotion_categories table for this
                // For now, we'll store this in a different way or skip
            }

            // Handle customers if applicable
            if ($request->has('customers') && is_array($request->customers)) {
                foreach ($request->customers as $customerId) {
                    $promotion->customers()->attach($customerId);
                }
            }

            DB::commit();
            return redirect()->route('promotions.index')
                ->with('success', 'تم إنشاء العرض الترويجي بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء إنشاء العرض الترويجي: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Promotion $promotion)
    {
        $promotion->load(['products.unit', 'customers']);
        return view('promotions.show', compact('promotion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Promotion $promotion)
    {
        $products = Product::where('is_active', 1)->orderBy('name')->get();
        $customers = Customer::where('is_active', 1)->orderBy('name')->get();
        $categories = Category::where('is_active', 1)->orderBy('name')->get();
        $units = Unit::where('is_active', 1)->orderBy('name')->get();
        
        $promotion->load(['products.unit', 'customers']);
        
        // تحضير بيانات عرض اشتر X واحصل على Y
        $buyProduct = null;
        $getProduct = null;
        
        if ($promotion->promotion_type == 'buy_x_get_y') {
            $buyProduct = $promotion->products->where('pivot.product_type', 'condition')->first();
            $getProduct = $promotion->products->where('pivot.product_type', 'reward')->first();
        }
        
        return view('promotions.edit', compact('promotion', 'products', 'customers', 'categories', 'units', 'buyProduct', 'getProduct'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Promotion $promotion)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'promotion_type' => 'required|in:simple_discount,buy_x_get_y,spend_x_save_y,coupon_code',
            'applies_to' => 'required|in:product,category,all',
            'discount_value' => 'required_if:promotion_type,simple_discount|nullable|numeric|min:0',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
        ]);
        
        // إضافة تحقق خاص بعرض اشتر X واحصل على Y
        if ($request->promotion_type == 'buy_x_get_y') {
            $request->validate([
                'buy_product_id' => 'required|exists:products,id',
                'buy_quantity' => 'required|numeric|min:1',
                'buy_unit_id' => 'required|exists:units,id',
                'get_product_id' => 'required|exists:products,id',
                'get_quantity' => 'required|numeric|min:1',
                'get_unit_id' => 'required|exists:units,id',
            ]);
        }

        $data = $request->all();
        // Set is_active explicitly from checkbox
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        DB::beginTransaction();
        try {
            $promotion->update($data);

            // حذف العلاقات السابقة
            $promotion->products()->detach();
            
            // معالجة عرض اشتر X واحصل على Y
            if ($request->promotion_type == 'buy_x_get_y') {
                // إضافة المنتج الذي يجب شراؤه (المنتج X)
                $promotion->products()->attach($request->buy_product_id, [
                    'unit_id' => $request->buy_unit_id,
                    'quantity' => $request->buy_quantity,
                    'product_type' => 'condition', // شرط للعرض
                ]);
                
                // إضافة المنتج الذي سيتم الحصول عليه مجانًا (المنتج Y)
                $promotion->products()->attach($request->get_product_id, [
                    'unit_id' => $request->get_unit_id,
                    'quantity' => $request->get_quantity,
                    'product_type' => 'reward', // مكافأة العرض
                ]);
            }
            // معالجة العروض الأخرى
            else if ($request->applies_to == 'product' && $request->has('products')) {
                foreach ($request->products as $productId) {
                    $promotion->products()->attach($productId, [
                        'unit_id' => $request->unit_id ?? null,
                        'quantity' => 1,
                        'product_type' => 'condition',
                    ]);
                }
            }

            // Handle categories if applicable
            if ($request->applies_to == 'category' && $request->has('categories')) {
                // We'll need to create a promotion_categories table for this
                // For now, we'll store this in a different way or skip
            }

            // Handle customers if applicable
            if ($request->has('customers')) {
                $promotion->customers()->sync($request->customers);
            } else {
                $promotion->customers()->detach();
            }

            DB::commit();
            return redirect()->route('promotions.index')
                ->with('success', 'تم تحديث العرض الترويجي بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء تحديث العرض الترويجي: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promotion $promotion)
    {
        try {
            $promotion->delete();
            return redirect()->route('promotions.index')
                ->with('success', 'تم حذف العرض الترويجي بنجاح.');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء حذف العرض الترويجي: ' . $e->getMessage());
        }
    }
    
    /**
     * Toggle the active status of the promotion.
     */
    public function toggleActive(Promotion $promotion)
    {
        $promotion->is_active = !$promotion->is_active;
        $promotion->save();
        
        return back()->with('success', 'تم تغيير حالة العرض الترويجي بنجاح.');
    }
    
    /**
     * تطبيق العرض الترويجي على سلة المشتريات
     * هذه الدالة يتم استدعاؤها من نظام المبيعات
     */
    public function applyPromotion(Request $request)
    {
        $request->validate([
            'promotion_id' => 'required|exists:promotions,id',
            'cart_items' => 'required|array',
            'customer_id' => 'nullable|exists:customers,id',
        ]);
        
        $promotion = Promotion::findOrFail($request->promotion_id);
        $cartItems = $request->cart_items;
        $customerId = $request->customer_id;
        
        // التحقق من صلاحية العرض
        if (!$promotion->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'العرض الترويجي غير نشط'
            ]);
        }
        
        if ($promotion->start_date && now() < $promotion->start_date) {
            return response()->json([
                'success' => false,
                'message' => 'لم يبدأ العرض الترويجي بعد'
            ]);
        }
        
        if ($promotion->end_date && now() > $promotion->end_date) {
            return response()->json([
                'success' => false,
                'message' => 'انتهى العرض الترويجي'
            ]);
        }
        
        // التحقق من أن العرض متاح للعميل
        if ($customerId && $promotion->customers->count() > 0) {
            $customerIds = $promotion->customers->pluck('id')->toArray();
            if (!in_array($customerId, $customerIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'العرض الترويجي غير متاح لهذا العميل'
                ]);
            }
        }
        
        // تطبيق العرض حسب نوعه
        $result = [];
        
        switch ($promotion->promotion_type) {
            case 'simple_discount':
                $result = $this->applySimpleDiscount($promotion, $cartItems);
                break;
                
            case 'buy_x_get_y':
                $result = $this->applyBuyXGetY($promotion, $cartItems);
                break;
                
            case 'spend_x_save_y':
                $result = $this->applySpendXSaveY($promotion, $cartItems);
                break;
                
            case 'coupon_code':
                // سيتم تنفيذه لاحقًا
                break;
        }
        
        return response()->json($result);
    }
    
    /**
     * تطبيق خصم بسيط على سلة المشتريات
     */
    private function applySimpleDiscount($promotion, $cartItems)
    {
        $discountedItems = [];
        $totalDiscount = 0;
        
        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            $unitId = $item['unit_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $subtotal = $price * $quantity;
            $discountAmount = 0;
            
            // التحقق مما إذا كان العرض ينطبق على هذا المنتج
            $applies = false;
            
            if ($promotion->applies_to == 'all') {
                $applies = true;
            } else if ($promotion->applies_to == 'product') {
                $productIds = $promotion->products->pluck('id')->toArray();
                $applies = in_array($productId, $productIds);
            } else if ($promotion->applies_to == 'category') {
                // تنفيذ التحقق من التصنيف
                // يحتاج إلى تنفيذ
            }
            
            if ($applies) {
                // حساب قيمة الخصم
                $discountAmount = $subtotal * ($promotion->discount_value / 100);
                
                // التحقق من الحد الأقصى للخصم
                if ($promotion->maximum_discount && $discountAmount > $promotion->maximum_discount) {
                    $discountAmount = $promotion->maximum_discount;
                }
                
                $totalDiscount += $discountAmount;
                
                $discountedItems[] = [
                    'product_id' => $productId,
                    'unit_id' => $unitId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'final_price' => $price - ($discountAmount / $quantity),
                ];
            } else {
                $discountedItems[] = [
                    'product_id' => $productId,
                    'unit_id' => $unitId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'final_price' => $price,
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => 'تم تطبيق الخصم بنجاح',
            'items' => $discountedItems,
            'total_discount' => $totalDiscount,
        ];
    }
    
    /**
     * تطبيق عرض اشتر X واحصل على Y
     */
    private function applyBuyXGetY($promotion, $cartItems)
    {
        // الحصول على منتج الشرط (X) ومنتج المكافأة (Y)
        $conditionProduct = $promotion->products->where('pivot.product_type', 'condition')->first();
        $rewardProduct = $promotion->products->where('pivot.product_type', 'reward')->first();
        
        if (!$conditionProduct || !$rewardProduct) {
            return [
                'success' => false,
                'message' => 'لم يتم تكوين العرض بشكل صحيح',
            ];
        }
        
        $conditionProductId = $conditionProduct->id;
        $conditionUnitId = $conditionProduct->pivot->unit_id;
        $conditionQuantity = $conditionProduct->pivot->quantity;
        
        $rewardProductId = $rewardProduct->id;
        $rewardUnitId = $rewardProduct->pivot->unit_id;
        $rewardQuantity = $rewardProduct->pivot->quantity;
        
        // البحث عن منتج الشرط في سلة المشتريات
        $conditionItemInCart = null;
        foreach ($cartItems as $item) {
            if ($item['product_id'] == $conditionProductId && $item['unit_id'] == $conditionUnitId) {
                $conditionItemInCart = $item;
                break;
            }
        }
        
        // إذا لم يكن منتج الشرط موجودًا في السلة أو الكمية غير كافية
        if (!$conditionItemInCart || $conditionItemInCart['quantity'] < $conditionQuantity) {
            return [
                'success' => false,
                'message' => 'لا تنطبق شروط العرض على سلة المشتريات',
            ];
        }
        
        // حساب عدد مرات تطبيق العرض
        $timesToApply = floor($conditionItemInCart['quantity'] / $conditionQuantity);
        
        // البحث عن منتج المكافأة في سلة المشتريات
        $rewardItemInCart = null;
        foreach ($cartItems as $key => $item) {
            if ($item['product_id'] == $rewardProductId && $item['unit_id'] == $rewardUnitId) {
                $rewardItemInCart = $item;
                $rewardItemKey = $key;
                break;
            }
        }
        
        // إنشاء نسخة من سلة المشتريات للتعديل
        $updatedCartItems = $cartItems;
        
        // إذا كان منتج المكافأة موجودًا في السلة
        if ($rewardItemInCart) {
            // حساب كمية المكافأة
            $freeQuantity = min($timesToApply * $rewardQuantity, $rewardItemInCart['quantity']);
            
            // حساب قيمة الخصم
            $discountAmount = $freeQuantity * $rewardItemInCart['price'];
            
            // تحديث سعر منتج المكافأة
            $updatedCartItems[$rewardItemKey]['discount_amount'] = $discountAmount;
            $updatedCartItems[$rewardItemKey]['final_price'] = $rewardItemInCart['price'] - ($discountAmount / $rewardItemInCart['quantity']);
            
            return [
                'success' => true,
                'message' => 'تم تطبيق العرض بنجاح',
                'items' => $updatedCartItems,
                'total_discount' => $discountAmount,
                'free_product' => [
                    'product_id' => $rewardProductId,
                    'unit_id' => $rewardUnitId,
                    'quantity' => $freeQuantity,
                ],
            ];
        } 
        // إذا كان منتج المكافأة غير موجود في السلة، نضيفه
        else {
            // الحصول على معلومات المنتج
            $rewardProduct = Product::find($rewardProductId);
            
            if (!$rewardProduct) {
                return [
                    'success' => false,
                    'message' => 'منتج المكافأة غير موجود',
                ];
            }
            
            // الحصول على سعر المنتج
            $productUnit = $rewardProduct->units()->where('unit_id', $rewardUnitId)->first();
            $price = $productUnit ? $productUnit->price : 0;
            
            // إضافة منتج المكافأة إلى السلة
            $freeQuantity = $timesToApply * $rewardQuantity;
            
            $updatedCartItems[] = [
                'product_id' => $rewardProductId,
                'unit_id' => $rewardUnitId,
                'quantity' => $freeQuantity,
                'price' => $price,
                'subtotal' => $price * $freeQuantity,
                'discount_amount' => $price * $freeQuantity, // خصم كامل (مجاني)
                'final_price' => 0,
                'is_free' => true,
            ];
            
            return [
                'success' => true,
                'message' => 'تم تطبيق العرض بنجاح',
                'items' => $updatedCartItems,
                'total_discount' => $price * $freeQuantity,
                'free_product' => [
                    'product_id' => $rewardProductId,
                    'unit_id' => $rewardUnitId,
                    'quantity' => $freeQuantity,
                ],
            ];
        }
    }
    
    /**
     * تطبيق عرض أنفق X ووفر Y
     */
    private function applySpendXSaveY($promotion, $cartItems)
    {
        // حساب إجمالي المشتريات
        $totalSpent = 0;
        foreach ($cartItems as $item) {
            $totalSpent += $item['price'] * $item['quantity'];
        }
        
        // التحقق من الحد الأدنى للشراء
        if ($promotion->minimum_purchase && $totalSpent < $promotion->minimum_purchase) {
            return [
                'success' => false,
                'message' => 'لم يتم الوصول إلى الحد الأدنى للشراء',
            ];
        }
        
        // حساب قيمة الخصم
        $discountAmount = $promotion->discount_value;
        
        // التحقق من الحد الأقصى للخصم
        if ($promotion->maximum_discount && $discountAmount > $promotion->maximum_discount) {
            $discountAmount = $promotion->maximum_discount;
        }
        
        // التأكد من أن الخصم لا يتجاوز إجمالي المشتريات
        if ($discountAmount > $totalSpent) {
            $discountAmount = $totalSpent;
        }
        
        // توزيع الخصم على المنتجات بشكل نسبي
        $updatedCartItems = [];
        foreach ($cartItems as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $itemDiscountAmount = ($subtotal / $totalSpent) * $discountAmount;
            
            $updatedCartItems[] = [
                'product_id' => $item['product_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
                'discount_amount' => $itemDiscountAmount,
                'final_price' => $item['price'] - ($itemDiscountAmount / $item['quantity']),
            ];
        }
        
        return [
            'success' => true,
            'message' => 'تم تطبيق الخصم بنجاح',
            'items' => $updatedCartItems,
            'total_discount' => $discountAmount,
        ];
    }
}
