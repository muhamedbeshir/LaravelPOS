<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\ProductUnit;
use App\Models\ProductPriceHistory;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\PriceType;
use Illuminate\Validation\Rule;
use App\Models\Setting;
use App\Models\ProductUnitBarcode;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductLog;

class ProductController extends Controller
{
    protected static array $middlewares = [
        'auth',
        'permission:view-products' => ['only' => ['index', 'show']],
        'permission:create-products' => ['only' => ['create', 'store']],
        'permission:edit-products' => ['only' => ['edit', 'update', 'editPrices', 'updatePrices', 'bulkEditPrices', 'bulkUpdatePrices', 'toggleActive']],
        'permission:delete-products' => ['only' => ['destroy']],
        'permission:print-barcode' => ['only' => ['printBarcode', 'printLabels']],
        'permission:import-products' => ['only' => ['import']],
        'permission:export-products' => ['only' => ['export']],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with([
            'category',
            'mainUnit',
            'units' => function($query) {
                $query->with(['unit', 'barcodes', 'prices' => function($query) {
                    $query->with('priceType');
                }]);
            }
        ]);
        
        // Filter by category if provided
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filter by search term
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhereHas('units.barcodes', function($q) use ($search) {
                      $q->where('barcode', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Set number of items per page
        $perPage = $request->input('perPage', 50);
        $perPage = in_array($perPage, [50, 100, 200]) ? $perPage : 50;
        
        $products = $query->orderBy('name')->paginate($perPage);
        $priceTypes = PriceType::where('is_active', true)->orderBy('sort_order')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return view('products.index', compact('products', 'priceTypes', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $priceTypes = PriceType::where('is_active', true)->orderBy('sort_order')->get();
        
        return view('products.create', compact('categories', 'units', 'priceTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Custom validation for unique units
        $request->validate([
            'units' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $unitIds = array_column($value, 'unit_id');
                    if (count($unitIds) !== count(array_unique($unitIds))) {
                        $fail('لا يمكن إضافة نفس الوحدة أكثر من مرة للمنتج الواحد.');
                    }
                },
            ],
        ]);

        // 2. Main validation rules
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
                'category_id' => 'required|exists:categories,id',
                'has_serial' => 'nullable|boolean',
            'serial_number' => 'required_if:has_serial,true|nullable|string|unique:products,serial_number',
                'alert_quantity' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'main_unit_index' => 'required|numeric|min:0|max:'.(count($request->units) - 1),
            'units.*.unit_id' => 'required|distinct|exists:units,id',
            'units.*.barcodes' => 'nullable|array',
            'units.*.barcodes.*' => [
                'nullable',
                'string',
                Rule::unique('product_unit_barcodes', 'barcode'),
                function ($attribute, $value, $fail) {
                    // Check against main product barcodes too for thoroughness
                    if (Product::where('barcode', $value)->exists()) {
                        $fail('هذا الباركود مستخدم مسبقاً في منتج آخر.');
                    }
                },
            ],
            'units.*.prices' => 'required|array|min:1',
            'units.*.prices.*.price_type_id' => 'required|exists:price_types,id',
            'units.*.prices.*.value' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use ($request) {
                    // Extract the index from the attribute string 'units.0.prices.1.value'
                    $parts = explode('.', $attribute);
                    $unitIndex = $parts[1];
                    $priceTypeId = $request->input("units.{$unitIndex}.prices.{$parts[3]}.price_type_id");
                    
                    // Find if this price type is the default one
                    $priceType = PriceType::find($priceTypeId);
                    
                    if ($priceType && $priceType->is_default && $value <= 0) {
                        $fail('السعر الافتراضي يجب أن يكون أكبر من صفر.');
                    }
                    if ($value < 0) {
                        $fail('لا يمكن أن يكون السعر قيمة سالبة.');
                    }
                },
            ],
        ], [
            'name.required' => 'اسم المنتج مطلوب.',
            'name.unique' => 'اسم المنتج مسجل مسبقاً.',
            'category_id.required' => 'يجب اختيار مجموعة للمنتج.',
            'main_unit_index.required' => 'يجب تحديد الوحدة الرئيسية للمنتج.',
            'units.*.unit_id.required' => 'يجب اختيار وحدة.',
            'units.*.unit_id.distinct' => 'لا يمكن تكرار نفس الوحدة.',
            'units.*.prices.*.value.required' => 'السعر مطلوب.',
        ]);

        try {
            DB::beginTransaction();

            $product = Product::create([
                'name' => $validatedData['name'],
                'category_id' => $validatedData['category_id'],
                'has_serial' => $validatedData['has_serial'] ?? false,
                'serial_number' => $validatedData['serial_number'] ?? null,
                'alert_quantity' => $validatedData['alert_quantity'] ?? 0,
                'is_active' => true,
                'image' => $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null,
            ]);

            \Log::info('Product created', ['product_id' => $product->id, 'name' => $product->name]);

            foreach ($validatedData['units'] as $index => $unitData) {
                $isMainUnit = ($index == $validatedData['main_unit_index']);

                $productUnit = $product->units()->create([
                    'unit_id' => $unitData['unit_id'],
                    'is_main_unit' => $isMainUnit,
                    'is_active' => true,
                ]);

                // Handle multiple barcodes - generate unique barcodes for empty fields
                $barcodes = $unitData['barcodes'] ?? [];
                $hasValidBarcodes = false;
                
                foreach ($barcodes as $barcode) {
                    if (!empty($barcode)) {
                        $productUnit->barcodes()->create(['barcode' => $barcode]);
                        $hasValidBarcodes = true;
                    }
                }
                
                // If no valid barcodes were provided, generate one automatically
                if (!$hasValidBarcodes) {
                    $generatedBarcode = $this->generateUniqueBarcode();
                    $productUnit->barcodes()->create(['barcode' => $generatedBarcode]);
                    
                    // Store the generated barcode for the main unit assignment
                    if ($isMainUnit) {
                        $product->barcode = $generatedBarcode;
                    }
                }

                if ($isMainUnit && $hasValidBarcodes) {
                    // Assign the first valid barcode as the main product barcode
                    foreach ($barcodes as $barcode) {
                        if (!empty($barcode)) {
                            $product->barcode = $barcode;
                            break;
                        }
                    }
                }

                foreach ($unitData['prices'] as $priceData) {
                    if (isset($priceData['value'])) {
                        $productUnitPrice = $productUnit->prices()->create([
                            'price_type_id' => $priceData['price_type_id'],
                            'value' => $priceData['value'],
                            'is_active' => true,
                        ]);
                        
                        // Add price history record for new price
                        $priceType = PriceType::find($priceData['price_type_id']);
                        $priceTypeName = $priceType ? $priceType->name : 'غير معروف';
                        
                        ProductPriceHistory::create([
                            'product_unit_id' => $productUnit->id,
                            'price_type_id' => $priceData['price_type_id'],
                            'price_type' => $priceTypeName,
                            'old_price' => 0,
                            'new_price' => $priceData['value'],
                            'change_percentage' => 100, // New price is 100% increase from 0
                            'change_type' => 'increase',
                            'changed_by' => auth()->id(),
                            'change_reason' => 'إنشاء منتج جديد'
                        ]);
                    }
                }
            }

            // Set main_unit_id on the product itself
            $mainProductUnit = $product->units()->where('is_main_unit', true)->first();
            if ($mainProductUnit) {
                $product->main_unit_id = $mainProductUnit->unit_id;
                $product->save();
            }

            DB::commit();

            // Check if user wants to print barcode after saving
            if ($request->has('print_barcode') && $request->print_barcode) {
                return redirect()->route('products.print-barcode', $product)
                    ->with('success', 'تم إنشاء المنتج بنجاح. يمكنك الآن طباعة الباركود.');
            }

            return redirect()->route('products.index')->with('success', 'تم إنشاء المنتج بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating product: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إنشاء المنتج. ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['category', 'units.unit']);
        
        // Add unit_name attribute to each unit to avoid template errors
        $product->units->each(function($productUnit) {
            if (isset($productUnit->unit) && $productUnit->unit) {
                $productUnit->unit_name = $productUnit->unit->name;
            } else {
                // Fallback to fetching unit directly
                $unit = Unit::find($productUnit->unit_id);
                $productUnit->unit_name = $unit ? $unit->name : 'Unknown Unit';
            }
        });
        
        if (request()->ajax()) {
            // Get the last purchase information using the same method as ProductUnitPriceController
            $lastPurchase = \App\Models\PurchaseItem::where('product_id', $product->id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            $lastPurchaseInfo = null;
            
            if ($lastPurchase) {
                $purchaseUnit = \App\Models\Unit::find($lastPurchase->unit_id);
                    
                $lastPurchaseInfo = [
                    'price' => $lastPurchase->purchase_price,
                    'unit_id' => $lastPurchase->unit_id,
                    'unit_name' => $purchaseUnit ? $purchaseUnit->name : 'الوحدة الافتراضية',
                    'date' => $lastPurchase->created_at
                ];
                
                \Log::info('Last purchase price found:', [
                    'product_id' => $product->id,
                    'purchase_price' => $lastPurchase->purchase_price,
                    'unit' => $purchaseUnit ? $purchaseUnit->name : 'Unknown',
                    'purchase_item_id' => $lastPurchase->id
                ]);
            } else {
                \Log::info('No purchase history found for product:', ['product_id' => $product->id]);
            }
            
            $imageUrl = $product->image ? asset('storage/products/' . $product->image) : null;
            
            return response()->json([
                'success' => true,
                'product' => $product->load(['category', 'units.unit']),
                'imageUrl' => $imageUrl,
                'last_purchase' => $lastPurchaseInfo
            ]);
        }

        return view('products.show', compact('product', 'priceTypes'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $priceTypes = PriceType::where('is_active', true)->orderBy('sort_order')->get();
        $product->load(['units.unit', 'units.prices']);
        
        return view('products.edit', compact('product', 'categories', 'units', 'priceTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        try {
            DB::beginTransaction();

            // Debug logging for duplicate name issue
            \Log::info('Product update debug', [
                'product_id' => $product->id,
                'request_name' => $request->name,
                'matching_products' => \App\Models\Product::where('name', $request->name)->get(['id', 'deleted_at'])
            ]);

            // التحقق من البيانات
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('products')->ignore($product->id)->whereNull('deleted_at')
                ],
                'category_id' => 'required|exists:categories,id',
                'has_serial' => 'boolean',
                'serial_number' => 'nullable|string|unique:products,serial_number,' . $product->id,
                'alert_quantity' => 'required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'units' => 'required|array|min:1',
                'units.*.unit_id' => 'required|exists:units,id',
                'units.*.barcodes' => 'nullable|array',
                'units.*.barcodes.*' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) use ($product) {
                        // Skip validation if value is empty
                        if (empty($value)) {
                            return;
                        }
                        
                        // Check for duplicates in the request itself
                        $allBarcodes = collect(request('units', []))
                            ->pluck('barcodes')
                            ->flatten()
                            ->filter()
                            ->toArray();
                        
                        if (count($allBarcodes) !== count(array_unique($allBarcodes))) {
                            $fail('يوجد باركود مكرر في الطلب');
                        }
                        
                        // Check existing barcodes excluding current product
                        $existingBarcode = \App\Models\ProductUnitBarcode::whereHas('productUnit', function($q) use ($product) {
                            $q->where('product_id', '!=', $product->id);
                        })->where('barcode', $value)->exists();
                        
                        if ($existingBarcode) {
                            $fail('هذا الباركود مستخدم في منتج آخر');
                        }
                    },
                ],
                'units.*.prices' => 'required|array|min:1',
                'units.*.prices.*.price_type_id' => 'required|exists:price_types,id',
                'units.*.prices.*.value' => 'required|numeric|min:0',
                'main_unit_index' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // تحديث بيانات المنتج
            $data = $request->except(['image', 'units', 'main_unit_index']);
            
            // معالجة الصورة
            if ($request->hasFile('image')) {
                // حذف الصورة القديمة
                if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
                    Storage::disk('public')->delete('products/' . $product->image);
                }

                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $request->file('image')->storeAs('products', $filename, 'public');
                
                if (!$path) {
                    throw new \Exception('فشل في حفظ الصورة');
                }

                $data['image'] = $filename;
            }

            $product->update($data);

            \Log::info('Product updated', ['product_id' => $product->id, 'name' => $product->name]);

            // حفظ الوحدات القديمة للمقارنة
            $oldUnits = $product->units()->with('unit')->get()->keyBy('unit_id');
            
            // تحديث وحدات المنتج
            $units = $request->input('units');
            $mainUnitIndex = $request->input('main_unit_index');
            
            // تتبع الوحدات التي تم تحديثها
            $updatedUnitIds = [];
            
            // مجموعة لتخزين unit_ids للتحقق من التكرار
            $processedUnitIds = [];
            
            // تحديث أو إضافة الوحدات
            foreach ($units as $index => $unitData) {
                try {
                    $unitId = $unitData['unit_id'];
                    
                    // التحقق من تكرار الوحدة
                    if (in_array($unitId, $processedUnitIds)) {
                        // تخطي الوحدة المكررة
                        \Log::warning("تم تخطي وحدة مكررة للمنتج: unit_id={$unitId}");
                        continue;
                    }
                    
                    // إضافة معرف الوحدة إلى القائمة
                    $processedUnitIds[] = $unitId;
                    $updatedUnitIds[] = $unitId;
                    
                    // تحديث الوحدة إذا كانت موجودة
                    if (isset($oldUnits[$unitId])) {
                        $productUnit = $oldUnits[$unitId];
                        
                        // تحديث البيانات الأساسية
                        $productUnit->update([
                            'is_main_unit' => ($index == $mainUnitIndex),
                            'is_active' => true
                        ]);
                        
                        // Handle multiple barcodes - generate unique barcodes for empty fields
                        if (isset($unitData['barcodes']) && is_array($unitData['barcodes'])) {
                            // Remove existing barcodes
                            $productUnit->barcodes()->delete();
                            
                            $hasValidBarcodes = false;
                            // Add new barcodes
                            foreach ($unitData['barcodes'] as $barcode) {
                                if (!empty($barcode)) {
                                    $productUnit->barcodes()->create(['barcode' => $barcode]);
                                    $hasValidBarcodes = true;
                                }
                            }
                            
                            // If no valid barcodes were provided, generate one automatically
                            if (!$hasValidBarcodes) {
                                $generatedBarcode = $this->generateUniqueBarcode();
                                $productUnit->barcodes()->create(['barcode' => $generatedBarcode]);
                                
                                // Store the generated barcode for the main unit assignment
                                if ($index == $mainUnitIndex) {
                                    $product->barcode = $generatedBarcode;
                                }
                            }
                        }
                        
                        // For backward compatibility - update the main price field
                        if (isset($unitData['prices']) && count($unitData['prices']) > 0) {
                            // Find default price type
                            $defaultPriceType = PriceType::where('is_default', true)->first();
                            if ($defaultPriceType) {
                                foreach ($unitData['prices'] as $priceData) {
                                    if (isset($priceData['price_type_id']) && $priceData['price_type_id'] == $defaultPriceType->id) {
                                        $productUnit->update([
                                            'main_price' => $priceData['value'] // Keep the main_price field updated
                                        ]);
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // تحديث أسعار الوحدة
                        if (isset($unitData['prices']) && is_array($unitData['prices'])) {
                            $sentPriceTypeIds = collect($unitData['prices'])->pluck('price_type_id')->map(fn($v) => (int)$v)->toArray();
                            // حذف الأسعار التي لم تعد موجودة
                            $productUnit->prices()->whereNotIn('price_type_id', $sentPriceTypeIds)->delete();
                            foreach ($unitData['prices'] as $priceData) {
                                if (isset($priceData['price_type_id']) && isset($priceData['value'])) {
                                    $priceTypeId = $priceData['price_type_id'];
                                    // البحث عن السعر وتحديثه أو إنشاء سعر جديد
                                    $price = $productUnit->prices()->firstOrNew(['price_type_id' => $priceTypeId]);
                                    // إذا كان السعر موجوداً، تسجيل التغيير في السعر
                                    if ($price->exists && $price->value != $priceData['value']) {
                                        $productUnit->savePriceHistory(
                                            'price_type_' . $priceTypeId,
                                            $price->value,
                                            $priceData['value']
                                        );
                                    }
                                    $price->value = $priceData['value'];
                                    $price->is_active = true;
                                    $price->save();
                                }
                            }
                        }
                    } else {
                        // إنشاء وحدة جديدة إذا لم تكن موجودة
                        $productUnit = $product->units()->create([
                            'unit_id' => $unitId,
                            'is_main_unit' => ($index == $mainUnitIndex),
                            'is_active' => true
                        ]);
                        
                        // Handle multiple barcodes for new unit - generate unique barcodes for empty fields
                        $hasValidBarcodes = false;
                        if (isset($unitData['barcodes']) && is_array($unitData['barcodes'])) {
                            foreach ($unitData['barcodes'] as $barcode) {
                                if (!empty($barcode)) {
                                    $productUnit->barcodes()->create(['barcode' => $barcode]);
                                    $hasValidBarcodes = true;
                                }
                            }
                        }
                        
                        // If no valid barcodes were provided, generate one automatically
                        if (!$hasValidBarcodes) {
                            $generatedBarcode = $this->generateUniqueBarcode();
                            $productUnit->barcodes()->create(['barcode' => $generatedBarcode]);
                            
                            // Store the generated barcode for the main unit assignment
                            if ($index == $mainUnitIndex) {
                                $product->barcode = $generatedBarcode;
                            }
                        } else if ($index == $mainUnitIndex) {
                            // Set main product barcode to first valid barcode
                            foreach ($unitData['barcodes'] as $barcode) {
                                if (!empty($barcode)) {
                                    $product->barcode = $barcode;
                                    break;
                                }
                            }
                        }
                        
                        // إضافة أسعار الوحدة
                        if (isset($unitData['prices']) && is_array($unitData['prices'])) {
                            foreach ($unitData['prices'] as $priceData) {
                                if (isset($priceData['price_type_id']) && isset($priceData['value'])) {
                                    // Find default price type for backward compatibility
                                    $defaultPriceType = PriceType::where('is_default', true)->first();
                                    if ($defaultPriceType && $priceData['price_type_id'] == $defaultPriceType->id) {
                                        $productUnit->update([
                                            'main_price' => $priceData['value'] // Keep main_price field updated
                                        ]);
                                    }
                                    
                                    // Save the price
                                    $productUnit->prices()->create([
                                        'price_type_id' => $priceData['price_type_id'],
                                        'value' => $priceData['value'],
                                        'is_active' => true
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    throw new \Exception('فشل في تحديث وحدة المنتج: ' . $e->getMessage());
                }
            }
            
            // حذف الوحدات التي لم تعد موجودة
            if (!empty($updatedUnitIds)) {
                $product->units()->whereNotIn('unit_id', $updatedUnitIds)->delete();
            }

            // Save the product to update the main barcode if it was changed
            $product->save();

            DB::commit();

            // Check if user wants to print barcode after updating
            if ($request->has('print_barcode') && $request->print_barcode) {
                return redirect()->route('products.print-barcode', $product)
                    ->with('success', 'تم تحديث المنتج بنجاح. يمكنك الآن طباعة الباركود.');
            }

            return redirect()->route('products.index')
                ->with('success', 'تم تحديث المنتج بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if (isset($filename) && Storage::disk('public')->exists('products/' . $filename)) {
                Storage::disk('public')->delete('products/' . $filename);
            }

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث المنتج: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            // حذف الصورة
            if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
                Storage::disk('public')->delete('products/' . $product->image);
            }

            // حذف المنتج ووحداته
            $product->units()->delete();
            $product->delete();

            \Log::info('Product deleted', ['product_id' => $product->id, 'name' => $product->name]);

            DB::commit();
            return redirect()->route('products.index')
                ->with('success', 'تم حذف المنتج بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف المنتج: ' . $e->getMessage());
        }
    }

    /**
     * Show the barcode printing page for a specific product.
     */
    public function printBarcode(Product $product)
    {
        $product->load(['units.unit', 'units.barcodes', 'units.prices.priceType']);
        $priceTypes = PriceType::where('is_active', true)->orderBy('sort_order')->get();
        $settings = Setting::pluck('value', 'key')->all();

        return view('products.barcode', compact('product', 'priceTypes', 'settings'));
    }

    /**
     * Prints barcode labels for a product.
     */
    public function printLabels(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'copies' => 'required|integer|min:1|max:100',
            'barcode_type' => 'required|string',
            'price_type_id' => 'required|exists:price_types,id',
            'barcode_id' => 'required|exists:product_unit_barcodes,id',
            'is_preview' => 'nullable|boolean',
        ]);

        $product = Product::with('units.unit')->findOrFail($validated['product_id']);
        $priceType = PriceType::findOrFail($validated['price_type_id']);
        $barcode = ProductUnitBarcode::with('productUnit.unit')->findOrFail($validated['barcode_id']);
        $productUnit = $barcode->productUnit;

        if (!$productUnit) {
            return response()->json(['error' => 'Product unit not found for the selected barcode.'], 422);
        }

        $price = $productUnit->prices()->where('price_type_id', $priceType->id)->first();

        // Prepare array for printing
        $products_to_print = [];
        $copies = ($validated['is_preview'] ?? false) ? 1 : $validated['copies'];

        // Get product name
        $product_name = $product->name;

        for ($i = 0; $i < $copies; $i++) {
            $products_to_print[] = [
                'name' => $product_name,
                'price' => $price ? $price->value : null,
                'barcode_value' => $barcode->barcode,
            ];
        }

        $data = [
            'product' => $product,
            'products_to_print' => $products_to_print,
            'barcode_type' => $validated['barcode_type'],
            'print_settings' => Setting::pluck('value', 'key')->all(),
            'is_preview' => $validated['is_preview'] ?? false,
        ];

        return view('products.print_labels', $data);
    }

    public function toggleActive(Product $product)
    {
        try {
            $product->is_active = !$product->is_active;
            $product->save();

            return redirect()->route('products.index')
                ->with('success', 'تم تحديث حالة المنتج بنجاح');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث حالة المنتج: ' . $e->getMessage());
        }
    }

    public function export()
    {
        try {
            // إنشاء ملف Excel جديد
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // تعيين اتجاه الورقة من اليمين لليسار
            $sheet->setRightToLeft(true);

            // تنسيق العناوين
            $sheet->getStyle('A1:H1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);

            // تعيين ارتفاع الصف الأول
            $sheet->getRowDimension(1)->setRowHeight(30);

            // إضافة العناوين
            $headers = [
                'الاسم',
                'المجموعة',
                'الباركود',
                'الرقم التسلسلي',
                'حد التنبيه',
                'الوحدة الرئيسية',
                'السعر الرئيسي',
                'الحالة'
            ];
            $sheet->fromArray([$headers], NULL, 'A1');

            // جلب البيانات
            $products = Product::with(['category', 'mainUnit'])->orderBy('name')->get();
            
            $row = 2;
            foreach ($products as $product) {
                $mainUnitName = '-';
                $mainPrice = '-';
                
                if ($product->mainUnit) {
                    // mainUnit is already a Unit model
                    $mainUnitName = $product->mainUnit->name;
                    $mainPrice = number_format($product->mainUnit()->first()->main_price ?? 0, 2);
                }
                
                $data = [
                    $product->name,
                    $product->category->name,
                    $product->barcode ?? '-',
                    $product->has_serial ? $product->serial_number : '-',
                    $product->alert_quantity,
                    $mainUnitName,
                    $mainPrice,
                    $product->is_active ? 'نشط' : 'غير نشط'
                ];
                
                $sheet->fromArray([$data], NULL, 'A' . $row);
                
                // تنسيق الصف
                $sheet->getStyle('A'.$row.':H'.$row)->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $row++;
            }

            // تعديل عرض الأعمدة لتناسب المحتوى
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // إنشاء ملف Excel
            $writer = new Xlsx($spreadsheet);
            
            // تحديد اسم الملف
            $fileName = 'products_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // تحديد headers لتنزيل الملف
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');

            // حفظ الملف مباشرة للتحميل
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تصدير المنتجات: ' . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray(new \stdClass(), $file)[0];

            // Remove header row
            $productData = array_slice($data, 1);

            DB::beginTransaction();

            $mainPriceType = PriceType::where('is_default', true)->first();
            if (!$mainPriceType) {
                // Create a default price type if none exists
                $mainPriceType = PriceType::create([
                    'name' => 'سعر البيع',
                    'is_default' => true,
                    'is_active' => true,
                ]);
            }

            foreach ($productData as $row) {
                // Assuming format: Name, Category, Barcode, Main Price, Purchase Price, Quantity
                if (empty($row[0]) || empty($row[2])) {
                    \Log::warning('Skipping row due to missing name or barcode.', ['row' => $row]);
                    continue; // Skip empty or invalid rows
                }

                $categoryName = $row[1] ?? 'Uncategorized';
                $category = Category::firstOrCreate(['name' => $categoryName]);

                $product = Product::updateOrCreate(
                    ['barcode' => $row[2]],
                    [
                        'name' => $row[0],
                        'category_id' => $category->id,
                        'is_active' => true,
                        'stock_quantity' => $row[5] ?? 0,
                    ]
                );

                // Find or create the main unit for the product
                $productUnit = $product->units()->where('is_main_unit', true)->first();
                if (!$productUnit) {
                    $unit = Unit::orderBy('id', 'asc')->first();
                    if (!$unit) {
                        $unit = Unit::create(['name' => 'قطعة', 'code' => 'PCE', 'is_active' => true]);
                    }
                    $productUnit = $product->units()->create([
                        'unit_id' => $unit->id,
                        'is_main_unit' => true,
                        'is_active' => true,
                    ]);
                }
                
                // Explicitly set the main_unit_id on the product model
                $product->main_unit_id = $productUnit->unit_id;
                $product->save();
                
                // Set the cost price from the import
                $productUnit->cost = $row[4] ?? 0;
                $productUnit->save();

                // Set the selling price from the import
                $productUnit->prices()->updateOrCreate(
                    ['price_type_id' => $mainPriceType->id],
                    ['value' => $row[3] ?? 0]
                );
            }

            DB::commit();
            
            // Clear caches to prevent routing issues after import
            Artisan::call('optimize:clear');

            return redirect()->route('products.index')->with('success', 'تم استيراد المنتجات بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Import Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->back()->with('error', 'حدث خطأ أثناء استيراد الملف: ' . $e->getMessage());
        }
    }

    public function priceHistory(Product $product)
    {
        $product->load(['category', 'units.unit', 'units.priceHistory']);
        return view('products.price_history', compact('product'));
    }

    /**
     * عرض صفحة التعديل الجماعي للأسعار
     */
    public function bulkEditPrices()
    {
        $products = Product::with(['category', 'productUnits.unit', 'productUnits.prices.priceType'])
            ->orderBy('name')
            ->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $priceTypes = PriceType::where('is_active', true)->orderBy('sort_order')->get();
        
        return view('products.bulk_edit_prices', compact('products', 'categories', 'priceTypes'));
    }

    /**
     * تحديث الأسعار بشكل جماعي
     */
    public function bulkUpdatePrices(Request $request)
    {
        try {
            // Debug log: record all submitted data
            \Log::info('Bulk Update Prices - Request Data:', [
                'edit_type' => $request->edit_type,
                'has_direct_prices' => $request->has('direct_prices'),
                'has_products' => $request->has('products'),
                'method' => $request->method(),
                'is_ajax' => $request->ajax(),
                'all' => $request->all()
            ]);
            
            DB::beginTransaction();

            if ($request->edit_type === 'direct') {
                // التحقق من البيانات للتعديل المباشر
                $validator = Validator::make($request->all(), [
                    'direct_prices.*.*.*' => 'nullable|numeric|min:0',
                    'purchase_prices.*' => 'nullable|numeric|min:0',
                ]);

                if ($validator->fails()) {
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }

                // تحديث سعر الشراء
                if ($request->has('purchase_prices')) {
                    foreach ($request->purchase_prices as $unitId => $costPrice) {
                        if (!is_null($costPrice)) {
                            ProductUnit::where('id', $unitId)->update(['cost' => $costPrice]);
                        }
                    }
                }

                // تحديث الأسعار مباشرة
                if ($request->has('direct_prices')) {
                    foreach ($request->direct_prices as $productId => $units) {
                        $product = Product::with('productUnits.prices')->find($productId);
                        if ($product) {
                            foreach ($units as $unitId => $pricesByType) {
                                $productUnit = $product->productUnits()->find($unitId);
                                if ($productUnit) {
                                    foreach ($pricesByType as $priceTypeId => $priceValue) {
                                        if (!empty($priceValue)) {
                                            // البحث عن السعر الموجود أو إنشاء جديد
                                            $productUnitPrice = $productUnit->prices()
                                                ->where('price_type_id', $priceTypeId)
                                                ->first();

                                            $oldPrice = $productUnitPrice ? $productUnitPrice->value : 0;

                                            if ($productUnitPrice) {
                                                $productUnitPrice->update(['value' => $priceValue]);
                                            } else {
                                                $productUnit->prices()->create([
                                                    'price_type_id' => $priceTypeId,
                                                    'value' => $priceValue
                                                ]);
                                            }

                                            ProductLog::create([
                                                'product_id' => $product->id,
                                                'event' => 'تغيير سعر البيع', // Selling price change
                                                'quantity' => $priceValue - $oldPrice,
                                                'reference' => 'تعديل مباشر',
                                            ]);
                                        } else {
                                            // البحث عن السعر الموجود أو إنشاء جديد
                                            $productUnitPrice = $productUnit->prices()
                                                ->where('price_type_id', $priceTypeId)
                                                ->first();
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    \Log::warning('Direct prices update requested but no direct_prices data provided');
                }
            } else { // 'bulk'
                // التحقق من البيانات للتعديل الجماعي
                $validator = Validator::make($request->all(), [
                    'products' => 'required|array|min:1',
                    'products.*' => 'exists:products,id',
                    'adjustment_type' => 'required|in:fixed,percentage',
                    'adjustment_value' => 'required|numeric',
                    'price_type_id' => 'required',
                    'operation' => 'required|in:increase,decrease,set'
                ]);

                if ($validator->fails()) {
                    \Log::error('Bulk Update Mass Adjustment - Validation Errors:', [
                        'errors' => $validator->errors()->toArray()
                    ]);
                    
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }

                // Debug log: Bulk update parameters
                \Log::info('Bulk Update - Parameters:', [
                    'product_count' => count($request->products),
                    'adjustment_type' => $request->adjustment_type,
                    'adjustment_value' => $request->adjustment_value,
                    'price_type_id' => $request->price_type_id,
                    'operation' => $request->operation,
                ]);

                $products = Product::with('productUnits.prices')
                    ->whereIn('id', $request->products)
                    ->get();

                $priceTypeIds = $request->price_type_id === 'all' 
                    ? PriceType::where('is_active', true)->pluck('id')->toArray()
                    : [$request->price_type_id];

                foreach ($products as $product) {
                    foreach ($product->productUnits as $unit) {
                        foreach ($priceTypeIds as $priceTypeId) {
                            $productUnitPrice = $unit->prices()
                                ->where('price_type_id', $priceTypeId)
                                ->first();

                            if ($productUnitPrice && $productUnitPrice->value > 0) {
                                $oldPrice = $productUnitPrice->value;
                                $newPrice = $this->calculateNewPrice(
                                    $oldPrice,
                                    $request->adjustment_type,
                                    $request->adjustment_value,
                                    $request->operation
                                );

                                if ($oldPrice != $newPrice && $newPrice >= 0) {
                                    $productUnitPrice->update(['value' => $newPrice]);
                                    
                                    \Log::info('Product price updated', ['product_id' => $product->id, 'price_type_id' => $priceTypeId, 'old_price' => $oldPrice, 'new_price' => $newPrice]);
                                    
                                    // تحديد نوع التغير (زيادة أو نقصان)
                                    $changeType = $newPrice > $oldPrice ? 'increase' : 'decrease';
                                    
                                    // الحصول على اسم نوع السعر
                                    $priceTypeName = PriceType::find($priceTypeId)->name ?? 'غير معروف';
                                    
                                    // تسجيل تغيير السعر في التاريخ
                                    ProductPriceHistory::create([
                                        'product_unit_id' => $unit->id,
                                        'price_type_id' => $priceTypeId,
                                        'price_type' => $priceTypeName, // إضافة حقل نوع السعر كنص
                                        'old_price' => $oldPrice,
                                        'new_price' => $newPrice,
                                        'change_percentage' => $oldPrice > 0 ? (($newPrice - $oldPrice) / $oldPrice) * 100 : 100,
                                        'change_type' => $changeType, // إضافة نوع التغير
                                        'changed_by' => auth()->id(),
                                        'change_reason' => 'تعديل جماعي بالنسبة/القيمة'
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('products.index')
                ->with('success', 'تم تحديث الأسعار بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث الأسعار: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * حساب السعر الجديد بناءً على نوع التعديل
     */
    private function calculateNewPrice($oldPrice, $adjustmentType, $adjustmentValue, $operation)
    {
        if ($operation === 'set') {
            return $adjustmentValue;
        }
        
        if ($adjustmentType === 'fixed') {
            return $operation === 'increase' 
                ? $oldPrice + $adjustmentValue 
                : $oldPrice - $adjustmentValue;
        } else { // percentage
            $adjustment = $oldPrice * ($adjustmentValue / 100);
            return $operation === 'increase' 
                ? $oldPrice + $adjustment 
                : $oldPrice - $adjustment;
        }
    }

    /**
     * عرض نموذج تعديل الأسعار
     */
    public function editPrices(Product $product)
    {
        $product->load(['productUnits.unit', 'productUnits.prices.priceType']);
        $priceTypes = PriceType::where('is_active', true)->orderBy('sort_order')->get();
        return view('products.edit_prices', compact('product', 'priceTypes'));
    }

    /**
     * تحديث أسعار المنتج
     */
    public function updatePrices(Request $request, Product $product)
    {
        try {
            // تسجيل البيانات المرسلة
            \Log::info('Single Product Prices Update - Request Data:', [
                'product_id' => $product->id,
                'all' => $request->all()
            ]);
            
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'units' => 'required|array',
                'units.*.id' => 'required|exists:product_units,id',
                'units.*.prices' => 'array',
                'units.*.prices.*' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                \Log::warning('Validation errors on product prices update:', [
                    'product_id' => $product->id,
                    'errors' => $validator->errors()->toArray()
                ]);
                
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            foreach ($request->units as $unitIndex => $unitData) {
                $unitId = $unitData['id'];
                
                // استخدام find مباشرة للعثور على وحدة المنتج
                $productUnit = ProductUnit::find($unitId);
                
                if (!$productUnit) {
                    \Log::warning("Product unit not found", [
                        'unit_id' => $unitId,
                        'product_id' => $product->id
                    ]);
                    continue;
                }
                
                // التحقق من أن وحدة المنتج تنتمي للمنتج الحالي
                if ($productUnit->product_id != $product->id) {
                    \Log::warning("Product unit does not belong to this product", [
                        'unit_id' => $unitId,
                        'unit_product_id' => $productUnit->product_id,
                        'product_id' => $product->id
                    ]);
                    continue;
                }
                
                // التحقق من وجود بيانات الأسعار
                if (!isset($unitData['prices'])) {
                    \Log::info("No prices data for unit", [
                        'unit_id' => $unitId,
                        'product_id' => $product->id
                    ]);
                    continue;
                }
                
                \Log::info("Processing prices for unit", [
                    'unit_id' => $unitId,
                    'prices' => $unitData['prices']
                ]);
                
                foreach ($unitData['prices'] as $priceTypeId => $priceValue) {
                    if (!empty($priceValue)) {
                        // البحث عن السعر الموجود أو إنشاء جديد
                        $productUnitPrice = $productUnit->prices()
                            ->where('price_type_id', $priceTypeId)
                            ->first();
                        
                        $oldPrice = $productUnitPrice ? $productUnitPrice->value : 0;
                        
                        if ($productUnitPrice) {
                            \Log::info("Updating existing price", [
                                'price_id' => $productUnitPrice->id,
                                'old_price' => $oldPrice,
                                'new_price' => $priceValue
                            ]);

                            $productUnitPrice->update(['value' => $priceValue]);

                            ProductLog::create([
                                'product_id' => $product->id,
                                'event' => 'تغيير سعر البيع (' . PriceType::find($priceTypeId)->name . ')',
                                'quantity' => $priceValue - $oldPrice,
                                'reference' => 'تعديل يدوي',
                            ]);
                        } else {
                            \Log::info("Creating new price", [
                                'unit_id' => $unitId,
                                'price_type_id' => $priceTypeId,
                                'price_value' => $priceValue
                            ]);
                            
                            $productUnit->prices()->create([
                                'price_type_id' => $priceTypeId,
                                'value' => $priceValue,
                                'is_active' => true
                            ]);
                        }
                        
                        // تسجيل تغيير السعر في التاريخ
                        if ($oldPrice != $priceValue) {
                            // حساب نسبة التغيير
                            $changePercentage = $oldPrice > 0 
                                ? (($priceValue - $oldPrice) / $oldPrice) * 100 
                                : 100;
                            
                            // تحديد نوع التغير (زيادة أو نقصان)
                            $changeType = $priceValue > $oldPrice ? 'increase' : 'decrease';
                            
                            // الحصول على نوع السعر
                            $priceType = PriceType::find($priceTypeId);
                            $priceTypeName = $priceType ? $priceType->name : 'غير معروف';
                            
                            ProductPriceHistory::create([
                                'product_unit_id' => $productUnit->id,
                                'price_type_id' => $priceTypeId,
                                'price_type' => $priceTypeName,
                                'old_price' => $oldPrice,
                                'new_price' => $priceValue,
                                'change_percentage' => $changePercentage,
                                'change_type' => $changeType,
                                'changed_by' => auth()->id(),
                                'change_reason' => 'تحديث مباشر'
                            ]);
                        }
                    } else {
                        // حذف السعر إذا كان فارغ
                        $deleted = $productUnit->prices()
                            ->where('price_type_id', $priceTypeId)
                            ->delete();
                            
                        \Log::info("Deleted empty price", [
                            'unit_id' => $unitId,
                            'price_type_id' => $priceTypeId,
                            'deleted_count' => $deleted
                        ]);
                    }
                }
            }

            DB::commit();
            \Log::info("Product prices updated successfully", ['product_id' => $product->id]);
            
            return redirect()->route('products.index')
                ->with('success', 'تم تحديث أسعار المنتج بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Error updating product prices", [
                'product_id' => $product->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث الأسعار: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض صفحة تحليل الأسعار
     */
    public function priceAnalytics()
    {
        $products = Product::with(['category', 'units.unit'])
            ->orderBy('name')
            ->get();
        
        return view('products.price_analytics', compact('products'));
    }

    /**
     * الحصول على بيانات تحليل الأسعار
     */
    public function getPriceAnalyticsData(Request $request)
    {
        try {
            $productId = $request->input('product_id');
            $priceType = $request->input('price_type', 'main_price');
            $timeRange = (int) $request->input('time_range', 30);

            // تحسين الاستعلام للحصول على البيانات
            $query = DB::table('product_price_history')
                ->join('product_units', 'product_price_history.product_unit_id', '=', 'product_units.id')
                ->join('products', 'product_units.product_id', '=', 'products.id')
                ->join('units', 'product_units.unit_id', '=', 'units.id')
                ->select([
                    'products.name as product_name',
                    'units.name as unit_name',
                    'product_price_history.old_price',
                    'product_price_history.new_price',
                    'product_price_history.price_type',
                    'product_price_history.created_at'
                ])
                ->where('product_price_history.price_type', $priceType)
                ->where('product_price_history.created_at', '>=', now()->subDays($timeRange));

            if ($productId) {
                $query->where('products.id', $productId);
            }

            $priceHistory = $query->orderBy('product_price_history.created_at', 'desc')->get();

            // تجهيز بيانات الرسم البياني
            $historyData = [
                'dates' => [],
                'datasets' => []
            ];

            // تجهيز إحصائيات التغييرات
            $statistics = [
                'averageChange' => 0,
                'highestIncrease' => 0,
                'highestDecrease' => 0,
                'changesCount' => $priceHistory->count()
            ];

            // تجهيز توزيع التغييرات
            $changesDistribution = [
                'increases' => 0,
                'decreases' => 0,
                'unchanged' => 0
            ];

            if ($priceHistory->count() > 0) {
                $totalChangePercentage = 0;
                $changes = [];

                foreach ($priceHistory as $change) {
                    // حساب نسبة التغيير
                    $changePercentage = $change->old_price > 0 
                        ? (($change->new_price - $change->old_price) / $change->old_price) * 100 
                        : 0;

                    // تحديث الإحصائيات
                    $totalChangePercentage += $changePercentage;
                    $statistics['highestIncrease'] = max($statistics['highestIncrease'], $changePercentage);
                    $statistics['highestDecrease'] = min($statistics['highestDecrease'], $changePercentage);

                    // تحديث توزيع التغييرات
                    if ($changePercentage > 0) {
                        $changesDistribution['increases']++;
                    } elseif ($changePercentage < 0) {
                        $changesDistribution['decreases']++;
                    } else {
                        $changesDistribution['unchanged']++;
                    }

                    // تجميع البيانات للرسم البياني
                    $date = \Carbon\Carbon::parse($change->created_at)->format('Y-m-d');
                    if (!in_array($date, $historyData['dates'])) {
                        $historyData['dates'][] = $date;
                    }

                    $changes[] = [
                        'product_name' => $change->product_name,
                        'unit_name' => $change->unit_name,
                        'old_price' => $change->old_price,
                        'new_price' => $change->new_price,
                        'price_type' => $change->price_type,
                        'created_at' => $change->created_at,
                        'change_percentage' => $changePercentage
                    ];
                }

                // حساب متوسط التغيير
                $statistics['averageChange'] = $totalChangePercentage / $priceHistory->count();

                // تجميع البيانات للرسم البياني
                sort($historyData['dates']);
                
                if ($productId) {
                    $productChanges = collect($changes)->groupBy('product_name');
                    foreach ($productChanges as $productName => $productHistory) {
                        $prices = [];
                        foreach ($historyData['dates'] as $date) {
                            $priceOnDate = $productHistory->first(function($change) use ($date) {
                                return \Carbon\Carbon::parse($change['created_at'])->format('Y-m-d') === $date;
                            });
                            $prices[] = $priceOnDate ? $priceOnDate['new_price'] : null;
                        }

                        $historyData['datasets'][] = [
                            'label' => $productName,
                            'data' => $prices,
                            'borderColor' => '#' . substr(md5($productName), 0, 6),
                            'fill' => false
                        ];
                    }
                }
            }

            return response()->json([
                'historyData' => $historyData,
                'statistics' => $statistics,
                'changesDistribution' => $changesDistribution,
                'changes' => $changes ?? []
            ]);

        } catch (\Exception $e) {
            \Log::error('خطأ في تحليل الأسعار: ' . $e->getMessage());
            return response()->json([
                'error' => 'حدث خطأ أثناء تحليل البيانات: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUnits(Product $product)
    {
        return response()->json([
            'units' => $product->units()->with(['unit', 'prices.priceType'])->get()
        ]);
    }

    /**
     * Generate a unique barcode that doesn't exist in the database
     */
    private function generateUniqueBarcode(): string
    {
        do {
            $barcode = (string) mt_rand(1000000000000, 9999999999999); // 13-digit barcode
        } while (\App\Models\ProductUnitBarcode::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public function showLog(Product $product)
    {
        $logs = ProductLog::where('product_id', $product->id)->orderBy('created_at', 'desc')->get();

        return view('products.log', compact('product', 'logs'));
    }
}
