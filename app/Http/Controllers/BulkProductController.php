<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PriceType;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductUnitPrice;
use App\Models\ProductUnitBarcode;
use App\Models\Unit;
use App\Models\Color;
use App\Models\Size;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\ProductPriceHistory;
use App\Models\Setting;

class BulkProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض نموذج إنشاء المنتجات السريعة
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $priceTypes = PriceType::all();
        $defaultPriceType = PriceType::where('is_default', true)->first();
        $colors = Color::orderBy('name')->get();
        $sizes = Size::orderBy('name')->get();

        $showColors = Setting::get('show_colors_options', true);
        $showSizes = Setting::get('show_sizes_options', true);
        
        return view('products.bulk_create', compact('categories', 'units', 'priceTypes', 'defaultPriceType', 'colors', 'sizes', 'showColors', 'showSizes'));
    }
    
    /**
     * تخزين المنتجات السريعة
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $productsData = $request->input('products', []);
            $commonData = $request->only(['category_id', 'unit_id', 'price_types']);
            $alertQuantity = $request->input('alert_quantity', 0);
            $enableInitialStock = $request->has('enable_initial_stock');
            $enableVariants = $request->has('enable_variants');
            $enableVariablePurchase = $enableVariants && $enableInitialStock && $request->has('enable_variable_purchase_prices');
            $enableVariableSelling = $enableVariants && $request->has('enable_variable_selling_prices');
            
            $totalVariantsCreated = 0;
            $colorsMap = $enableVariants ? Color::pluck('name', 'id')->toArray() : [];
            $sizesMap = $enableVariants ? Size::pluck('name', 'id')->toArray() : [];

            foreach ($productsData as $data) {
                if (empty($data['name'])) continue;

                $customVariants = $enableVariants && !empty($data['variants']) ? json_decode($data['variants'], true) : [];

                if (empty($customVariants)) {
                    // --- الحالة 1: لا توجد متغيرات ---
                    // يتم إنشاء منتج واحد بالوحدة والأسعار الخاصة به
                    $product = Product::create([
                        'name' => $data['name'],
                        'category_id' => $commonData['category_id'],
                        'alert_quantity' => $alertQuantity,
                        'is_active' => true,
                    ]);

                    $purchasePrice = $enableInitialStock ? ($data['purchase_price'] ?? 0) : 0;
                    $productUnit = $this->createProductUnit($product, $commonData['unit_id'], $purchasePrice);
                    $this->createUnitPrices($productUnit, $data['prices'] ?? []);

                    // Generate or use provided barcode for regular products
                    $barcode = isset($data['barcode']) && trim($data['barcode']) !== '' ? 
                        trim($data['barcode']) : $this->generateUniqueBarcode();
                    \Log::info('Regular product barcode:', ['barcode' => $barcode, 'product' => $data['name']]);
                    
                    // Store the barcode in ProductUnitBarcode table
                    $productUnit->barcodes()->create(['barcode' => $barcode]);
                    
                    // Also store the barcode in product record for easier access
                    $product->barcode = $barcode;
                    $product->save();
                    
                    // Set main_unit_id on the product itself
                    $product->main_unit_id = $commonData['unit_id'];
                    $product->save();
                    
                    $variant = $this->createVariant(
                        $product, null, null, $data['name'],
                        $barcode,
                        $enableInitialStock ? ($data['initial_quantity'] ?? 0) : 0
                    );
                    $this->updateProductStock($product, $variant, $commonData['unit_id'], $enableInitialStock);
                    
                    if ($enableInitialStock && $purchasePrice > 0) {
                        $this->updateAllUnitsCost($product->id, $commonData['unit_id'], $purchasePrice);
                    }
                    $totalVariantsCreated++;

                } else {
                    // --- الحالة 2: توجد متغيرات ---
                    // يتم إنشاء منتج مستقل لكل متغير
                    \Log::info('Processing variants data:', ['variants_data' => $customVariants]);
                    
                    foreach ($customVariants as $variantData) {
                        $colorId = !empty($variantData['colorId']) ? $variantData['colorId'] : null;
                        $sizeId = !empty($variantData['sizeId']) ? $variantData['sizeId'] : null;
                        
                        $variantName = $data['name'];
                        if ($colorId && isset($colorsMap[$colorId])) $variantName .= ' - ' . $colorsMap[$colorId];
                        if ($sizeId && isset($sizesMap[$sizeId])) $variantName .= ' - ' . $sizesMap[$sizeId];
                        
                        // إنشاء منتج جديد لكل متغير
                        $product = Product::create([
                            'name' => $variantName,
                            'category_id' => $commonData['category_id'],
                            'alert_quantity' => $alertQuantity,
                            'is_active' => true,
                        ]);
                        
                        // تحديد أسعار الشراء والبيع بناءً على الإعدادات
                        if ($enableVariablePurchase) {
                            $purchasePrice = $variantData['purchase_price'] ?? 0;
                        } else {
                            $purchasePrice = $enableInitialStock ? ($data['purchase_price'] ?? 0) : 0;
                        }
                        
                        if ($enableVariableSelling) {
                            $sellingPrices = $variantData['prices'] ?? [];
                        } else {
                             $sellingPrices = $data['prices'] ?? [];
                        }

                        $productUnit = $this->createProductUnit($product, $commonData['unit_id'], $purchasePrice);
                        $this->createUnitPrices($productUnit, $sellingPrices);
                        
                        $variantQuantity = $enableInitialStock && isset($variantData['quantity']) ? $variantData['quantity'] : 0;
                        
                        // Generate or use provided barcode for variants
                        $variantBarcode = isset($variantData['barcode']) && trim($variantData['barcode']) !== '' ? 
                            trim($variantData['barcode']) : $this->generateUniqueBarcode();
                        \Log::info('Variant barcode:', [
                            'barcode' => $variantBarcode, 
                            'variant' => $variantName,
                            'raw_data' => $variantData
                        ]);
                        
                        // Store the barcode in ProductUnitBarcode table
                        $productUnit->barcodes()->create(['barcode' => $variantBarcode]);
                        
                        // Also store the barcode in product record for easier access
                        $product->barcode = $variantBarcode;
                        $product->save();

                        // Set main_unit_id on the product itself
                        $product->main_unit_id = $commonData['unit_id'];
                        $product->save();

                        $productVariant = $this->createVariant(
                            $product, $colorId, $sizeId, $variantName,
                            $variantBarcode,
                            $variantQuantity
                        );
                        $this->updateProductStock($product, $productVariant, $commonData['unit_id'], $enableInitialStock);
                        
                        if ($enableInitialStock && $purchasePrice > 0) {
                            $this->updateAllUnitsCost($product->id, $commonData['unit_id'], $purchasePrice);
                        }
                        $totalVariantsCreated++;
                    }
                }
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', "تم إضافة {$totalVariantsCreated} منتج/متغير بنجاح.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء إضافة المنتجات: ' . $e->getMessage())->withInput();
        }
    }

    private function createProductUnit($product, $unitId, $purchasePrice)
    {
        return $product->units()->create([
            'unit_id' => $unitId,
            'is_main_unit' => true,
            'conversion_factor' => 1,
            'cost' => $purchasePrice, // Store purchase price in 'cost' column
            'is_active' => true,
        ]);
    }

    private function createUnitPrices($productUnit, $prices)
    {
        if (empty($prices)) return;

        foreach ($prices as $priceTypeId => $price) {
            if (!empty($price)) {
                $productUnitPrice = $productUnit->prices()->create([
                    'price_type_id' => $priceTypeId,
                    'value' => $price,
                    'is_active' => true,
                ]);
                
                // Add price history record for new price - exactly matching ProductController
                $priceType = PriceType::find($priceTypeId);
                $priceTypeName = $priceType ? $priceType->name : 'غير معروف';
                
                ProductPriceHistory::create([
                    'product_unit_id' => $productUnit->id,
                    'price_type_id' => $priceTypeId,
                    'price_type' => $priceTypeName,
                    'old_price' => 0,
                    'new_price' => $price,
                    'change_percentage' => 100, // New price is 100% increase from 0
                    'change_type' => 'increase',
                    'changed_by' => auth()->id(),
                    'change_reason' => 'إنشاء منتج جديد'
                ]);
            }
        }
    }

    private function createVariant($product, $colorId, $sizeId, $name, $barcode, $initialStock)
    {
        // Enhanced barcode handling to ensure it always has a value
        // Add debug logging to track barcode values
        \Log::info('Creating variant with barcode:', ['input_barcode' => $barcode, 'product_name' => $name]);
        
        $finalBarcode = (!empty($barcode)) ? $barcode : $this->generateUniqueBarcode();
        \Log::info('Final barcode value:', ['final_barcode' => $finalBarcode]);

        return $product->variants()->create([
            'color_id' => $colorId,
            'size_id' => $sizeId,
            'name' => $name,
            'barcode' => $finalBarcode,
            'stock_quantity' => $initialStock,
        ]);
    }

    private function updateProductStock($product, $variant, $unitId, $enableInitialStock)
    {
        if ($enableInitialStock && $variant->stock_quantity > 0) {
            try {
                $purchasePrice = $product->units()->where('is_main_unit', true)->first()->cost ?? 0;

                $product->updateStock(
                    $variant->stock_quantity,
                    $unitId,
                    'add',
                    [
                        'purchase_price' => $purchasePrice,
                        'reference_type' => 'initial_stock',
                        'reference_id'   => $variant->id,
                        'employee_id'    => null, // لا نحتاج إلى ربط الحركة بموظف محدد
                        'notes'          => 'رصيد افتتاحي للمتغير'
                    ]
                );
            } catch (\Exception $e) {
                \Log::error('فشل في تحديث المخزون للمنتج: ' . $e->getMessage(), [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => $variant->stock_quantity,
                    'unit_id' => $unitId
                ]);
                // نتابع التنفيذ حتى لو فشلت عملية تحديث المخزون، لا نريد إيقاف إنشاء المنتج
                // لكن نقوم بتسجيل الخطأ لمعالجته لاحقاً
            }
        }
    }

    /**
     * تحديث تكلفة جميع وحدات المنتج بناءً على تكلفة الوحدة المشتراة.
     * يضمن هذا أن تكون تكاليف جميع الوحدات (مثل قطعة، علبة، كرتونة)
     * متناسبة مع بعضها البعض بناءً على عوامل التحويل.
     *
     * @param int $productId معرف المنتج
     * @param int $purchasedUnitId معرف الوحدة التي تم شراؤها فعليًا
     * @param float $costForPurchasedUnit سعر التكلفة للوحدة المشتراة
     * @return void
     */
    private function updateAllUnitsCost($productId, $purchasedUnitId, $costForPurchasedUnit)
    {
        try {
            
            $product = \App\Models\Product::with(['units.unit.parentUnit'])->find($productId);
            if (!$product) {
                
                return;
            }

            $purchasedProductUnit = $product->units->firstWhere('unit_id', $purchasedUnitId);
            if (!$purchasedProductUnit || !$purchasedProductUnit->unit) {
                
                return;
            }

            // احصل على إجمالي معامل التحويل للوحدة المشتراة إلى الوحدة الأساسية (القطعة)
            // هذه هي عدد القطع في الوحدة المشتراة
            $piecesInPurchasedUnit = $purchasedProductUnit->unit->getPiecesInUnit();
            if ($piecesInPurchasedUnit == 0) { // تجنب القسمة على صفر أو خطأ في التحويل
                
                return;
            }

            // حساب تكلفة القطعة الواحدة بناءً على الوحدة المشتراة
            $costPerPiece = $costForPurchasedUnit / $piecesInPurchasedUnit;
            

            // الآن قم بتحديث تكلفة كل وحدة من وحدات المنتج
            foreach ($product->units as $productUnitToUpdate) {
                if (!$productUnitToUpdate->unit) {
                    continue;
                }

                // احصل على إجمالي معامل التحويل لهذه الوحدة إلى القطعة
                $piecesInThisUnit = $productUnitToUpdate->unit->getPiecesInUnit();
                if ($piecesInThisUnit == 0 && !$productUnitToUpdate->unit->is_base_unit) {
                    // إذا لم تكن الوحدة الأساسية ولم يتم حساب عدد القطع (ربما بسبب خطأ أو دورة)
                    // سجل تحذيرًا وتخطاها بدلاً من تعيين التكلفة إلى صفر بشكل غير صحيح
                    // Log::warning("Could not determine pieces for unit ID: {$productUnitToUpdate->unit->id} ('{$productUnitToUpdate->unit->name}') for product ID: {$productId}. Skipping cost update for this unit.");
                    continue;
                }

                // تكلفة هذه الوحدة هي تكلفة القطعة مضروبة في عدد القطع فيها
                $newCostForThisUnit = $costPerPiece * $piecesInThisUnit;
                
                $productUnitToUpdate->cost = $newCostForThisUnit;
                $productUnitToUpdate->save();

                
            }

        } catch (\Exception $e) {
            
        }
    }
    
    /**
     * توليد باركود فريد
     */
    private function generateUniqueBarcode()
    {
        do {
            $barcode = mt_rand(1000000000000, 9999999999999);
        } while (
            ProductVariant::where('barcode', (string)$barcode)->exists() ||
            ProductUnitBarcode::where('barcode', (string)$barcode)->exists()
        );
        
        \Log::info('Generated unique barcode:', ['barcode' => $barcode]);
        return (string) $barcode;
    }
} 