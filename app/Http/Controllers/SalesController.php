<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Unit;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\Setting;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    public function index()
    {
        // Get inventory settings to pass to the view
        $settings = Setting::whereIn('key', [
            'allow_negative_inventory',
            'subtract_inventory_on_zero',
            'show_profit_in_summary',
            'show_profit_in_sales_table',
            'show_expiry_dates',
            'default_price_type',
            'allow_selling_at_different_prices',
            'allow_price_edit_during_sale'
        ])->pluck('value', 'key');

        // Determine if cost data needs to be loaded
        $loadCostData = $settings->get('show_profit_in_summary', false) || $settings->get('show_profit_in_sales_table', false);

        // This is the corrected query, moved from the Blade view
        $categories = Category::with([
            'products' => function($query) use ($loadCostData) {
                $query->active()
                    ->select(['id', 'name', 'barcode', 'category_id', 'stock_quantity', 'is_active', 'image'])
                    ->with([
                        'units' => function($q) use ($loadCostData) {
                            $q->active()
                              ->select(['id', 'product_id', 'unit_id', 'is_active']) // Corrected: removed 'barcode'
                              ->with([
                                  'unit' => function($uq) {
                                      $uq->select(['id', 'name', 'is_base_unit', 'parent_unit_id', 'conversion_factor']);
                                  },
                                  'prices' => function($pq) {
                                      $pq->with('priceType');
                                  },
                                  'barcodes' // Corrected: added barcodes relationship
                              ]);
                        },
                        'mainUnit' => function($q) {
                            $q->select(['id', 'name', 'is_base_unit', 'parent_unit_id', 'conversion_factor']);
                        }
                    ]);
            }
        ])
        ->select(['id', 'name', 'is_active'])
        ->active()
        ->get();

        // Only select needed fields from customers and employees
        $customers = Customer::active()
            ->with(['defaultPriceType:id,code,name'])
            ->select(['id', 'name', 'phone', 'address', 'payment_type', 'credit_balance', 'credit_limit', 'is_unlimited_credit', 'default_price_type_id'])
            ->orderBy('name')
            ->get();
            
        $employees = Employee::active()
            ->select(['id', 'name', 'job_title_id', 'phone', 'is_active'])
            ->orderBy('name')
            ->get();

        $priceTypes = \App\Models\PriceType::active()->orderBy('sort_order')->get();

        return view('sales.index', compact('categories', 'customers', 'employees', 'priceTypes', 'settings'));
    }

    public function getProducts(Request $request)
    {
        try {
            $search = $request->input('q', $request->input('search'));
            
            // Build optimized query with eager loading
            $query = Product::with([
                'category:id,name,color',
                'units' => function($q) {
                    $q->with([
                        'unit:id,name,is_base_unit,conversion_factor,parent_unit_id',
                        'prices.priceType'
                    ]);
                },
                'mainUnit:id,name,is_base_unit,conversion_factor'
            ])
            ->select(['id', 'name', 'barcode', 'stock_quantity', 'category_id', 'main_unit_id', 'is_active', 'image'])
            ->active();
            
            // Apply search filter if provided
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            }
            
            // Apply category filter if provided
            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }
            
            // Get paginated results with optimized loading
            $perPage = $request->input('per_page', 15);
            $products = $query->paginate($perPage);
            
            // Get price type from request
            $priceType = $request->input('price_type');
            
            // Format results for response
            $formattedProducts = $products->map(function($product) use ($priceType) {
                // Find the main product unit
                $mainProductUnit = $product->units->firstWhere('unit_id', $product->main_unit_id);
                
                // Get the price for the main unit based on the selected price type
                $mainUnitPrice = 0;
                if ($mainProductUnit) {
                    $mainUnitPrice = $this->getUnitPrice($mainProductUnit, $priceType);
                }
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'stock' => $product->stock_quantity, // Keep for potential compatibility if needed elsewhere
                    'stock_quantity' => $product->stock_quantity,
                    'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'color' => $product->category->color ?? '#6c757d'
                    ] : null,
                    'unit' => $product->mainUnit ? $product->mainUnit->name : null, // Main unit name
                    'price' => $mainUnitPrice, // Add the main unit price here
                    // Keep the detailed units array if it's used elsewhere, 
                    // otherwise it could be removed for performance.
                    /*
                    'units' => $product->units->map(function($unit) use ($priceType) {
                        return [
                            'id' => $unit->unit_id,
                            'name' => $unit->unit ? $unit->unit->name : 'Unknown',
                            'price' => $this->getUnitPrice($unit, $priceType),
                            'conversion' => $unit->unit ? $unit->unit->conversion_factor : 1
                        ];
                    })
                    */
                ];
            });
            
            return response()->json([
                'success' => true,
                'products' => $formattedProducts,
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving products: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProductUnits(Request $request, Product $product)
    {
        try {
            $units = $product->units()
                ->active()
                ->with(['unit', 'product', 'prices.priceType'])
                ->get()
                ->map(function($productUnit) use ($product) {
                    if (!$productUnit->unit) {
                        return null;
                    }

                    // Get the price for the requested price type
                    $price = $this->getUnitPrice($productUnit, request('price_type'));

                    // Calculate available stock
                    $stock = $product->stock_quantity;
                    $unitModel = $productUnit->unit;
                    
                    if ($unitModel->is_base_unit) {
                        // Base unit (piece)
                        $availableStock = $stock;
                        $conversionInfo = 'وحدة رئيسية';
                        $totalConversionFactor = 1;
                    } else {
                        $parentUnit = Unit::find($unitModel->parent_unit_id);
                        if ($parentUnit && $parentUnit->is_base_unit) {
                            // Direct conversion from base unit (like box)
                            $totalConversionFactor = $unitModel->conversion_factor;
                            $availableStock = floor($stock / $totalConversionFactor);
                            $conversionInfo = "1 {$unitModel->name} = {$totalConversionFactor} قطعة";
                        } else if ($parentUnit) {
                            // Nested conversion (like carton -> box -> piece)
                            $totalConversionFactor = $unitModel->conversion_factor;
                            
                            // If parent unit is not the base unit, get its conversion factor too
                            $grandParentUnit = $parentUnit->parent_unit_id ? Unit::find($parentUnit->parent_unit_id) : null;
                            if ($grandParentUnit) {
                                $totalConversionFactor *= $parentUnit->conversion_factor;
                            }
                            
                            $availableStock = floor($stock / $totalConversionFactor);
                            $conversionInfo = "1 {$unitModel->name} = {$totalConversionFactor} قطعة";
                        } else {
                            $totalConversionFactor = $unitModel->conversion_factor ?: 1;
                            $availableStock = floor($stock / $totalConversionFactor);
                            $conversionInfo = "1 {$unitModel->name} = {$totalConversionFactor} قطعة";
                        }
                    }
                    
                    // Get all prices for this unit
                    $prices = $productUnit->prices->mapWithKeys(function($price) {
                        return [$price->priceType->code => $price->value];
                    });

                    return [
                        'id' => $unitModel->id,
                        'unit_id' => $unitModel->id,
                        'name' => $unitModel->name,
                        'product_unit_id' => $productUnit->id,
                        'barcodes' => $productUnit->barcodes->pluck('barcode'), // Return all barcodes
                        'price' => $price,
                        'stock' => $availableStock,
                        'conversion_info' => $conversionInfo,
                        'conversion_factor' => $totalConversionFactor,
                        'is_base_unit' => $unitModel->is_base_unit,
                        'prices' => $prices
                    ];
                })
                ->filter() // Remove null entries
                ->values(); // Reset array indices

            return response()->json([
                'success' => true,
                'units' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error getting product units: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تخزين عملية بيع جديدة مع ربطها بالوردية المفتوحة
     */
    public function store(Request $request)
    {
        // التحقق من وجود وردية مفتوحة
        $currentShift = Shift::getCurrentOpenShift();
        if (!$currentShift) {
            return response()->json(['error' => 'لا توجد وردية مفتوحة حالياً. يرجى فتح وردية أولاً.'], 400);
        }
        
        // Start database transaction
        DB::beginTransaction();
        
        try {
            // Log transaction start
            \Log::info('Starting sales transaction', [
                'customer_id' => $request->customer_id,
                'type' => $request->type,
                'order_type' => $request->order_type,
                'items_count' => count($request->items ?? []),
            ]);

            // التحقق من البيانات
            $validated = $request->validate([
                'type' => 'required|in:cash,credit',
                'order_type' => 'required|in:takeaway,delivery',
                'customer_id' => 'required_if:type,credit',
                'delivery_employee_id' => 'required_if:order_type,delivery',
                'price_type' => 'required|in:retail,wholesale,distributor',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.unit_id' => 'required|exists:units,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.discount_value' => 'nullable|numeric|min:0',
                'items.*.discount_percentage' => 'nullable|numeric|between:0,100',
                'paid_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);

            // إنشاء الفاتورة
            $invoice = new Invoice();
            $invoice->type = $request->type;
            $invoice->order_type = $request->order_type;
            $invoice->customer_id = $request->customer_id;
            $invoice->delivery_employee_id = $request->delivery_employee_id;
            $invoice->price_type = $request->price_type;
            $invoice->paid_amount = $request->paid_amount;
            $invoice->notes = $request->notes;
            $invoice->invoice_number = Invoice::generateNumber();
            $invoice->shift_id = $currentShift->id;
            $invoice->shift_invoice_number = $this->getShiftInvoiceNumber();
            $invoice->save();

            \Log::info('Invoice created', ['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'total' => $invoice->total]);

            // Preload products and units for better performance
            $productIds = collect($request->items)->pluck('product_id')->unique()->toArray();
            $unitIds = collect($request->items)->pluck('unit_id')->unique()->toArray();
            
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $units = Unit::whereIn('id', $unitIds)->with('parentUnit')->get()->keyBy('id');

            // تحقق من المخزون إذا كان البيع بالسالب غير مسموح
            $allowNegativeInventory = \App\Models\Setting::get('allow_negative_inventory', false);
            if (!$allowNegativeInventory) {
                foreach ($request->items as $item) {
                    $product = $products[$item['product_id']] ?? null;
                    if ($product && !$product->hasEnoughStock($item['quantity'], $item['unit_id'])) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'لا يمكن بيع المنتج "' . $product->name . '". الكمية المتوفرة أقل من المطلوبة (المتوفر: ' . $product->getStockQuantity($item['unit_id']) . '، المطلوب: ' . $item['quantity'] . '). السماح بالمخزون السالب غير مفعل.',
                        ], 422);
                    }
                }
            }

            // إضافة المنتجات
            $stockUpdateErrors = [];
            $invoiceItems = [];
            
            foreach ($request->items as $index => $item) {
                $product = $products[$item['product_id']] ?? null;
                $unit = $units[$item['unit_id']] ?? null;
                
                if (!$product) {
                    $stockUpdateErrors[] = "المنتج غير موجود: Product ID " . $item['product_id'];
                    continue;
                }
                
                if (!$unit) {
                    $stockUpdateErrors[] = "الوحدة غير موجودة: Unit ID " . $item['unit_id'];
                    continue;
                }
                
                if (!$product->hasEnoughStock($item['quantity'], $item['unit_id'])) {
                    $stockUpdateErrors[] = "الكمية المطلوبة غير متوفرة للمنتج: {$product->name}";
                    continue;
                }

                $invoiceItem = new InvoiceItem();
                $invoiceItem->invoice_id = $invoice->id;
                $invoiceItem->product_id = $item['product_id'];
                $invoiceItem->unit_id = $item['unit_id'];
                $invoiceItem->quantity = $item['quantity'];
                $invoiceItem->unit_price = $item['unit_price'];
                $invoiceItem->discount_value = $item['discount_value'] ?? 0;
                $invoiceItem->discount_percentage = $item['discount_percentage'] ?? 0;
                $invoiceItem->save();
                
                $invoiceItems[] = $invoiceItem;
            }
            
            // إذا كان هناك أخطاء في تحديث المخزون، قم بالتراجع عن العملية
            if (!empty($stockUpdateErrors)) {
                DB::rollBack();
                \Log::warning('Sales transaction cancelled due to stock issues', [
                    'errors' => $stockUpdateErrors
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'حدثت مشاكل أثناء التحقق من المخزون',
                    'errors' => $stockUpdateErrors
                ], 422);
            }

            // Batch process inventory updates
            $stockBatchOperations = [];
            
            foreach ($invoiceItems as $item) {
                // Get the ProductUnit to find the actual unit_id from units table
                $productUnit = ProductUnit::find($item->unit_id);
                if (!$productUnit) {
                    throw new \Exception("ProductUnit not found for ID: {$item->unit_id}");
                }
                
                $unit = $productUnit->unit;
                if (!$unit) {
                    throw new \Exception("Unit not found for ProductUnit ID: {$item->unit_id}");
                }
                
                $conversionFactor = 1;
                if (!$unit->is_base_unit) {
                    $conversionFactor = $unit->conversion_factor;
                    if ($unit->parent_unit_id) {
                        $parentUnit = Unit::find($unit->parent_unit_id);
                        if ($parentUnit) {
                            $conversionFactor *= $parentUnit->conversion_factor;
                        }
                    }
                }
                
                $stockBatchOperations[] = [
                    'product_id' => $item->product_id,
                    'unit_id' => $unit->id, // Use the actual units.id, not product_units.id
                    'product_unit_id' => $item->unit_id, // Keep track of the product_unit for reference
                    'quantity' => $item->quantity,
                    'conversion_factor' => $conversionFactor,
                    'invoice_id' => $invoice->id
                ];
            }
            
            // Process all stock operations in batch
            $result = $this->batchUpdateInventory($stockBatchOperations);
            
            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تحديث المخزون',
                    'errors' => $result['errors']
                ], 500);
            }
            
            // حساب إجماليات الفاتورة
            $invoice->calculateTotals();

            // التأكد من تطابق إجمالي الدفعات مع إجمالي الفاتورة في حالة mixed
            if ($invoice->type === 'mixed') {
                if (abs($invoice->paid_amount - $invoice->total) > 0.01) {
                    throw new \Exception('إجمالي الدفعات لا يطابق إجمالي الفاتورة');
                }
            }

            // في حالة الدليفري
            if ($invoice->order_type === 'delivery') {
                // TODO: إنشاء جدول التوصيل
            }

            // تحديث رصيد العميل أو موظف الدليفري
            $invoice->updateBalances();
            
            // Update the shift's sales amounts in real-time
            if ($currentShift) {
                // Determine which field to update based on payment type
                switch ($invoice->type) {
                    case 'cash':
                        $currentShift->cash_sales += $invoice->total;
                        break;
                    case 'visa':
                        $currentShift->visa_sales += $invoice->total;
                        break;
                    case 'card':
                        $currentShift->card_sales += $invoice->total;
                        break;
                    case 'transfer':
                        $currentShift->bank_transfer_sales += $invoice->total;
                        break;
                    case 'bank':
                        $currentShift->bank_transfer_sales += $invoice->total;
                        break;
                    case 'wallet':
                        $currentShift->wallet_sales += $invoice->total;
                        break;
                }
                
                // Update the expected balance
                $currentShift->expected_closing_balance = 
                    $currentShift->opening_balance + 
                    $currentShift->cash_sales - 
                    $currentShift->withdrawal_amount - 
                    $currentShift->returns_amount;

                // Save the updated shift
                $currentShift->save();

                \Log::info('Updated shift sales amounts', [
                    'shift_id' => $currentShift->id,
                    'invoice_id' => $invoice->id,
                    'total' => $invoice->total
                ]);
            }

            \Log::info('Invoice updated', ['invoice_id' => $invoice->id, 'total' => $invoice->total]);

            // Commit transaction only if everything succeeded
            DB::commit();
            
            \Log::info('Sales transaction completed successfully', [
                'invoice_id' => $invoice->id,
                'total_amount' => $invoice->total,
                'items_count' => count($invoiceItems)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الفاتورة بنجاح',
                'invoice' => $invoice->load(['items.product', 'items.unit'])
            ]);

        } catch (\Exception $e) {
            // Roll back transaction on any error
            DB::rollBack();
            
            \Log::error('Sales transaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Batch process inventory updates for better performance
     * 
     * @param array $operations The stock operations to perform
     * @return array Result with success status and any errors
     */
    private function batchUpdateInventory($operations)
    {
        try {
            // Validate operations
            if (empty($operations)) {
                return [
                    'success' => true,
                    'message' => 'No inventory operations to process'
                ];
            }
            
            try {
                DB::beginTransaction();
                
                foreach ($operations as $op) {
                    $product = Product::find($op['product_id']);
                    
                    if (!$product) {
                        throw new \Exception("Product not found: {$op['product_id']}");
                    }
                    
                    // تحقق صارم من الكمية المتوفرة إذا كان المخزون السالب غير مسموح
                    $allowNegativeInventory = \App\Models\Setting::get('allow_negative_inventory', false);
                    if (!$allowNegativeInventory) {
                        if (!$product->hasEnoughStock($op['quantity'], $op['unit_id'])) {
                            throw new \Exception('لا يمكن خصم الكمية المطلوبة من المنتج "' . $product->name . '". الكمية المتوفرة أقل من المطلوبة (المتوفر: ' . $product->getStockQuantity($op['unit_id']) . '، المطلوب: ' . $op['quantity'] . '). السماح بالمخزون السالب غير مفعل.');
                        }
                    }

                    // Set employee_id to null for sales operations
                    // TODO: Implement proper employee lookup once employee records are set up
                    $employeeId = null;

                    // Use StockMovement::recordMovement for consistency
                    StockMovement::recordMovement([
                        'product_id' => $op['product_id'],
                        'unit_id' => $op['unit_id'], // This is now the correct units.id
                        'quantity' => $op['quantity'],
                        'movement_type' => 'out', // Sales always decrease stock
                        'reference_type' => 'App\\Models\\Invoice',
                        'reference_id' => $op['invoice_id'] ?? null,
                        'employee_id' => $employeeId,
                        'notes' => 'فاتورة مبيعات - رقم الفاتورة: ' . ($op['invoice_id'] ?? 'غير معروف')
                    ]);
                    
                    \Log::info('Stock movement recorded for sale', [
                        'product_id' => $op['product_id'],
                        'unit_id' => $op['unit_id'],
                        'quantity_sold' => $op['quantity'],
                        'conversion_factor' => $op['conversion_factor'] ?? 1,
                        'invoice_id' => $op['invoice_id'] ?? null
                    ]);
                }
                
                DB::commit();
                return [
                    'success' => true
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Batch inventory update failed', [
                    'error' => $e->getMessage(),
                    'operations' => $operations
                ]);
                return [
                    'success' => false,
                    'errors' => [$e->getMessage()]
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('Batch inventory update validation failed', [
                'error' => $e->getMessage(),
                'operations' => $operations
            ]);
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Get product details for sales
     */
    public function getProduct($id)
    {
        try {
            $product = Product::with([
                'category:id,name', 
                'units' => function($q) {
                    $q->with([
                        'unit:id,name,is_base_unit,conversion_factor,parent_unit_id',
                        'prices.priceType'
                    ]);
                },
                'mainUnit:id,name,is_base_unit,conversion_factor'
            ])
            ->select(['id', 'name', 'barcode', 'stock_quantity', 'category_id', 'main_unit_id', 'image'])
            ->findOrFail($id);
            
            // Load last purchase info if available
            $lastPurchase = DB::table('purchase_items')
                ->where('product_id', $id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $purchaseUnit = null;
            $purchaseUnitPieces = 1;
            $piecePrice = 0;
            
            if ($lastPurchase) {
                $purchaseUnit = DB::table('units')
                    ->where('id', $lastPurchase->unit_id)
                    ->first();
                
                if ($purchaseUnit) {
                    $purchaseUnitPieces = $purchaseUnit->is_base_unit ? 1 : $purchaseUnit->conversion_factor;
                    
                    if (!$purchaseUnit->is_base_unit && $purchaseUnit->parent_unit_id) {
                        $purchaseParentUnit = DB::table('units')
                            ->where('id', $purchaseUnit->parent_unit_id)
                            ->first();
                        
                        if ($purchaseParentUnit) {
                            $purchaseUnitPieces *= $purchaseParentUnit->conversion_factor;
                        }
                    }
                    
                    $piecePrice = $lastPurchase->purchase_price / $purchaseUnitPieces;
                }
            }
            
            // Preprocess product units with cost
            $unitsWithCost = [];
            
            foreach ($product->units as $productUnit) {
                $unit = $productUnit->unit;
                if (!$unit) continue;
                
                $costPerUnit = $productUnit->cost ?? 0;
                $conversionFactor = $unit->is_base_unit ? 1 : $unit->conversion_factor;
                
                if (!$unit->is_base_unit && $unit->parent_unit_id) {
                    $parentUnit = DB::table('units')
                        ->where('id', $unit->parent_unit_id)
                        ->first();
                    
                    if ($parentUnit) {
                        $conversionFactor *= $parentUnit->conversion_factor;
                    }
                }
                
                // Format price data
                $priceData = [];
                foreach ($productUnit->prices as $price) {
                    if ($price->priceType) {
                        $priceData[] = [
                            'price_type_id' => $price->price_type_id,
                            'price_type_name' => $price->priceType->name,
                            'price_type_code' => $price->priceType->code,
                            'value' => $price->value,
                            'is_default' => $price->priceType->is_default
                        ];
                    }
                }
                
                $conversionInfo = $unit->is_base_unit 
                    ? 'وحدة رئيسية' 
                    : sprintf(
                        '1 %s = %s قطعة',
                        $unit->name,
                        $conversionFactor
                    );
                
                $unitsWithCost[] = [
                    'id' => $productUnit->id,
                    'unit_id' => $unit->id,
                    'name' => $unit->name,
                    'barcode' => $productUnit->barcode,
                    'cost' => $costPerUnit,
                    'is_main_unit' => $productUnit->is_main_unit,
                    'conversion_factor' => $conversionFactor,
                    'conversion_info' => $conversionInfo,
                    'stock' => floor($product->stock_quantity / $conversionFactor),
                    'prices' => $priceData
                ];
            }
            
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'stock_quantity' => $product->stock_quantity,
                    'main_unit_id' => $product->main_unit_id,
                    'units' => $unitsWithCost,
                    'last_purchase' => [
                        'unit_id' => $lastPurchase->unit_id ?? null,
                        'unit_name' => $purchaseUnit->name ?? null,
                        'price' => $lastPurchase->purchase_price ?? 0,
                        'pieces' => $purchaseUnitPieces,
                        'piece_price' => $piecePrice
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب بيانات المنتج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for a product by barcode or name
     */
    public function searchProduct(Request $request)
    {
        try {
            // Special case: If search term is "*", return all products (limited to prevent performance issues)
            if ($request->filled('q') && $request->q === '*') {
                $products = Product::with(['category:id,name,color', 'mainUnit:id,name'])
                    ->select(['id', 'name', 'barcode', 'stock_quantity', 'category_id', 'main_unit_id', 'image'])
                    ->active()
                    ->limit(50) // Limit to 50 products for performance
                    ->get();
                
                if ($products->isNotEmpty()) {
                    $formattedProducts = $products->map(function($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'barcode' => $product->barcode ?? '-',
                            'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                            'category' => $product->category ? [
                                'id' => $product->category->id,
                                'name' => $product->category->name,
                                'color' => $product->category->color ?? '#6c757d'
                            ] : null,
                            'unit' => $product->mainUnit ? $product->mainUnit->name : null,
                            'stock_quantity' => $product->stock_quantity
                        ];
                    });
                    
                    return response()->json([
                        'success' => true,
                        'products' => $formattedProducts,
                        'multiple' => true
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد منتجات متاحة'
                ], 404);
            }
            
            // Check if there's a search term (either barcode or name)
            // or a category filter
            if (!$request->filled('barcode') && !$request->filled('q') && !$request->filled('search') && !$request->filled('category_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'الرجاء إدخال الباركود أو اسم المنتج للبحث، أو اختيار تصنيف'
                ], 422);
            }

            // If barcode is provided, search by barcode first
            if ($request->filled('barcode')) {
                $barcode = $request->barcode;
                
                // First, check if this is a specific unit barcode
                $productUnitBarcode = \App\Models\ProductUnitBarcode::where('barcode', $barcode)
                    ->with(['productUnit' => function($puQuery) {
                        $puQuery->where('is_active', true)
                                ->with(['product' => function($pQuery) {
                                    $pQuery->where('is_active', true)
                                           ->select(['id', 'name', 'barcode', 'stock_quantity', 'category_id', 'main_unit_id', 'image'])
                                           ->with(['category', 'mainUnit']);
                                }, 'unit', 'prices.priceType']);
                    }])
                    ->first();

                // If a specific unit barcode is found for an active product unit and an active product
                if ($productUnitBarcode && $productUnitBarcode->productUnit && $productUnitBarcode->productUnit->product) {
                    $productUnit = $productUnitBarcode->productUnit;
                    $product = $productUnit->product;
                    
                    // Return with specific unit information
                    return response()->json([
                        'success' => true,
                        'product' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'barcode' => $product->barcode, // Main product barcode
                            'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                            'category' => $product->category ? [
                                'id' => $product->category->id,
                                'name' => $product->category->name
                            ] : null,
                            'unit' => $product->mainUnit ? [
                                'id' => $product->mainUnit->id,
                                'name' => $product->mainUnit->name
                            ] : null,
                            'stock_quantity' => $product->stock_quantity
                        ],
                        'unit' => [
                            'id' => $productUnit->id,
                            'unit_id' => $productUnit->unit_id,
                            'name' => $productUnit->unit ? $productUnit->unit->name : 'Unknown Unit',
                            'barcode' => $productUnitBarcode->barcode, // The specific barcode that was scanned
                            'is_main_unit' => $productUnit->is_main_unit,
                            'conversion_factor' => $productUnit->unit->conversion_factor ?? 1,
                            'stock_quantity' => floor($product->stock_quantity / ($productUnit->unit->conversion_factor ?? 1)),
                            'prices' => $productUnit->prices->map(function($price) {
                                return [
                                    'id' => $price->id,
                                    'price_type_id' => $price->price_type_id,
                                    'price_type_name' => $price->priceType->name,
                                    'price_type_code' => $price->priceType->code,
                                    'value' => $price->value,
                                    'is_default' => $price->priceType->is_default
                                ];
                            })
                        ],
                        'multiple' => false,
                        'is_unit_barcode' => true
                    ]);
                }
                
                // If no specific unit barcode was found, try to find a product with this barcode as its main barcode
                $product = Product::where('barcode', $barcode)
                    ->select(['id', 'name', 'barcode', 'stock_quantity', 'category_id', 'main_unit_id', 'image'])
                    ->active()
                    ->with(['category', 'mainUnit'])
                    ->first();
                
                if ($product) {
                    // Check the setting to determine behavior for main product barcode
                    $showUnitsModal = Setting::where('key', 'show_units_modal_on_product_barcode')->value('value') ?? '1';
                    
                    if ($showUnitsModal === '1') {
                        // Setting is ON: Return product normally (will trigger unit selection modal if multiple units)
                        return response()->json([
                            'success' => true,
                            'product' => [
                                'id' => $product->id,
                                'name' => $product->name,
                                'barcode' => $product->barcode,
                                'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                                'category' => $product->category ? [
                                    'id' => $product->category->id,
                                    'name' => $product->category->name
                                ] : null,
                                'unit' => $product->mainUnit ? [
                                    'id' => $product->mainUnit->id,
                                    'name' => $product->mainUnit->name
                                ] : null,
                                'stock_quantity' => $product->stock_quantity
                            ]
                        ]);
                    } else {
                        // Setting is OFF: Add main unit directly
                        
                        // Find the main unit
                        $mainUnit = $product->units()->where('is_main_unit', true)
                            ->with(['unit', 'prices.priceType', 'barcodes'])
                            ->first();
                        
                        if ($mainUnit) {
                            return response()->json([
                                'success' => true,
                                'product' => [
                                    'id' => $product->id,
                                    'name' => $product->name,
                                    'barcode' => $product->barcode,
                                    'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                                    'category' => $product->category ? [
                                        'id' => $product->category->id,
                                        'name' => $product->category->name
                                    ] : null,
                                    'unit' => $product->mainUnit ? [
                                        'id' => $product->mainUnit->id,
                                        'name' => $product->mainUnit->name
                                    ] : null,
                                    'stock_quantity' => $product->stock_quantity
                                ],
                                'unit' => [
                                    'id' => $mainUnit->id,
                                    'unit_id' => $mainUnit->unit_id,
                                    'name' => $mainUnit->unit ? $mainUnit->unit->name : 'Unknown Unit',
                                    'barcode' => $mainUnit->barcodes->first()->barcode ?? $product->barcode, // Use first barcode or main product barcode
                                    'is_main_unit' => $mainUnit->is_main_unit,
                                    'conversion_factor' => $mainUnit->unit->conversion_factor ?? 1,
                                    'stock_quantity' => floor($product->stock_quantity / ($mainUnit->unit->conversion_factor ?? 1)),
                                    'prices' => $mainUnit->prices->map(function($price) {
                                        return [
                                            'id' => $price->id,
                                            'price_type_id' => $price->price_type_id,
                                            'price_type_name' => $price->priceType->name,
                                            'price_type_code' => $price->priceType->code,
                                            'value' => $price->value,
                                            'is_default' => $price->priceType->is_default
                                        ];
                                    })
                                ],
                                'multiple' => false,
                                'is_unit_barcode' => true,
                                'force_main_unit' => true
                            ]);
                        }
                        
                        // Fallback to normal product response if no main unit found
                        return response()->json([
                            'success' => true,
                            'product' => [
                                'id' => $product->id,
                                'name' => $product->name,
                                'barcode' => $product->barcode,
                                'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                                'category' => $product->category ? [
                                    'id' => $product->category->id,
                                    'name' => $product->category->name
                                ] : null,
                                'unit' => $product->mainUnit ? [
                                    'id' => $product->mainUnit->id,
                                    'name' => $product->mainUnit->name
                                ] : null,
                                'stock_quantity' => $product->stock_quantity
                            ]
                        ]);
                    }
                }
            }

            // If search term is provided or barcode search didn't find anything
            // or if category_id is provided
            if ($request->filled('q') || $request->filled('search') || $request->filled('category_id')) {
                $search = $request->input('q', $request->input('search'));
                
                // Search for products matching the name
                // Add eager loading with category and main unit
                $query = Product::query();
                
                // Apply name/barcode search if provided
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                              ->orWhere('barcode', 'like', "%{$search}%")
                              ->orWhereHas('units.barcodes', function($barcodeQuery) use ($search) {
                                  $barcodeQuery->where('barcode', 'like', "%{$search}%");
                              });
                    });
                }
                
                // Apply category filter if provided
                if ($request->filled('category_id')) {
                    $query->where('category_id', $request->input('category_id'));
                }
                
                $products = $query->with(['category:id,name,color', 'mainUnit:id,name'])
                    ->select(['id', 'name', 'barcode', 'stock_quantity', 'category_id', 'main_unit_id', 'image'])
                    ->active()
                    ->limit(100) // Increased limit for category browsing
                    ->get();
                
                if ($products->isNotEmpty()) {
                    $formattedProducts = $products->map(function($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'barcode' => $product->barcode ?? '-',
                            'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                            'category' => $product->category ? [
                                'id' => $product->category->id,
                                'name' => $product->category->name,
                                'color' => $product->category->color ?? '#6c757d'
                            ] : null,
                            'unit' => $product->mainUnit ? $product->mainUnit->name : null,
                            'stock_quantity' => $product->stock_quantity
                        ];
                    });
                    
                    return response()->json([
                        'success' => true,
                        'products' => $formattedProducts,
                        'multiple' => true
                    ]);
                }
            }
            
            // If no products found by either method
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على منتجات مطابقة'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في البحث: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new invoice
     */
    public function storeInvoice(Request $request)
    {
        try {
            DB::beginTransaction();

            // Simplified validation for diagnostics
            $validator = Validator::make($request->all(), [
                'invoice' => 'required|array',
                'invoice.customer_id' => 'required',
                'invoice.invoice_type' => 'required',
                'invoice.items' => 'required|array|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات الفاتورة غير صحيحة',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }

            $invoiceData = $request->input('invoice');
            
            // Check Customer ID exists
            $customer = \App\Models\Customer::find($invoiceData['customer_id']);
            if (!$customer) {
                // If customer ID is 1 and it doesn't exist, create a default cash customer
                if ($invoiceData['customer_id'] == 1) {
                    try {
                        $customer = new \App\Models\Customer();
                        $customer->id = 1;
                        $customer->name = 'عميل نقدي';
                        $customer->payment_type = 'cash';
                        $customer->is_active = true;
                        $customer->credit_limit = 0;
                        $customer->credit_balance = 0;
                        $customer->save();
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'فشل في إنشاء العميل النقدي الافتراضي',
                            'errors' => ['customer_id' => ['Failed to create default customer']]
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'رقم العميل غير صحيح',
                        'errors' => ['customer_id' => ['Customer not found']]
                    ], 422);
                }
            }
            
            // Create the invoice
            $invoice = new \App\Models\Invoice();
            $invoice->invoice_number = $this->generateInvoiceNumber();
            $invoice->customer_id = $invoiceData['customer_id'];
            $invoice->type = $invoiceData['invoice_type'];
            $invoice->order_type = $invoiceData['order_type'] ?? 'takeaway';
            $invoice->price_type = $invoiceData['price_type'] ?? 'retail';
            $invoice->discount_value = $invoiceData['discount_value'] ?? 0;
            $invoice->discount_percentage = $invoiceData['discount_percentage'] ?? 0;
            $invoice->paid_amount = $invoiceData['paid_amount'] ?? 0;
            $invoice->delivery_employee_id = $invoiceData['delivery_employee_id'] ?? null;
            $invoice->status = 'completed'; // Default status
            $invoice->payment_status = 'paid'; // Will be updated later
            $invoice->delivery_status = ($invoiceData['order_type'] ?? 'takeaway') === 'delivery' ? 'pending' : null;
            $invoice->notes = $invoiceData['notes'] ?? null;
            
            // Add the current shift if one is open
            $currentShift = \App\Models\Shift::getCurrentOpenShift();
            if ($currentShift) {
                $invoice->shift_id = $currentShift->id;
                $invoice->shift_invoice_number = $this->getShiftInvoiceNumber();
            }
            
            $invoice->save();
            
            // Store the current invoice ID in the session for stock movement reference
            request()->session()->put('current_invoice_id', $invoice->id);

            // Process invoice items
            $subtotal = 0;
            $profit = 0;
            
            // Collect all inventory operations for batch processing
            $inventoryOperations = [];
            
            // Prepare invoice items for bulk insert
            $invoiceItems = [];
            $now = now();
            
            foreach ($invoiceData['items'] as $item) {
                // Get product cost information for profit calculation
                $product = Product::find($item['product_id']);
                
                // تعديل هنا: البحث عن وحدة المنتج بدلاً من الوحدة العامة
                $productUnit = \App\Models\ProductUnit::where('product_id', $item['product_id'])
                                                   ->where('id', $item['unit_id'])
                                                   ->first();
                
                if (!$productUnit) {
                    // إذا لم يتم العثور على وحدة المنتج، نحاول البحث باستخدام unit_id كمعرف للوحدة العامة
                    $productUnit = \App\Models\ProductUnit::where('product_id', $item['product_id'])
                                                       ->where('unit_id', $item['unit_id'])
                                                       ->first();
                    
                    if (!$productUnit) {
                        return response()->json([
                            'success' => false,
                            'message' => "لم يتم العثور على وحدة المنتج. معرف المنتج: {$item['product_id']}, معرف الوحدة: {$item['unit_id']}"
                        ], 422);
                    }
                }
                
                // استخدام معرف وحدة المنتج (product_units.id) بدلاً من unit_id
                $unitId = $productUnit->id;
                $unit = $productUnit->unit; // الحصول على الوحدة العامة من وحدة المنتج
                $costPerUnit = $productUnit->cost ?? $this->getProductCostPerUnit($product, $productUnit->unit_id);
                
                // Calculate price after discount
                $discountValue = $item['discount_value'] ?? 0;
                $discountPercentage = $item['discount_percentage'] ?? 0;
                $priceAfterDiscount = $item['unit_price'];
                
                if ($discountPercentage > 0) {
                    $priceAfterDiscount = $item['unit_price'] * (1 - ($discountPercentage / 100));
                } elseif ($discountValue > 0) {
                    $priceAfterDiscount = $item['unit_price'] - $discountValue;
                }
                
                // Calculate total price and profit
                $totalPrice = $priceAfterDiscount * $item['quantity'];
                $itemProfit = ($priceAfterDiscount - $costPerUnit) * $item['quantity'];
                
                // Update running totals
                $subtotal += $totalPrice;
                $profit += $itemProfit;
                
                // Prepare invoice item for bulk insert
                $invoiceItems[] = [
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $unitId, // استخدام معرف وحدة المنتج
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $costPerUnit,
                    'discount_value' => $discountValue,
                    'discount_percentage' => $discountPercentage,
                    'price_after_discount' => $priceAfterDiscount,
                    'total_price' => $totalPrice,
                    'profit' => $itemProfit,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
                
                // Calculate unit conversion factor
                $conversionFactor = 1;
                if ($unit && !$unit->is_base_unit) {
                    $conversionFactor = $unit->conversion_factor;
                    if ($unit->parent_unit_id) {
                        $parentUnit = Unit::find($unit->parent_unit_id);
                        if ($parentUnit) {
                            $conversionFactor *= $parentUnit->conversion_factor;
                        }
                    }
                }
                
                // Collect inventory operation for batch processing
                $inventoryOperations[] = [
                    'product_id' => $item['product_id'],
                    'unit_id' => $unit->id, // استخدام معرف الوحدة العامة للمخزون
                    'quantity' => $item['quantity'],
                    'conversion_factor' => $conversionFactor,
                    'invoice_id' => $invoice->id
                ];
            }
            
            // Bulk insert all invoice items in a single query
            \App\Models\InvoiceItem::insert($invoiceItems);

            // Process all inventory updates in a single batch
            $batchResult = $this->batchUpdateInventory($inventoryOperations);
            
            // Check if inventory update was successful
            if (!$batchResult['success']) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تحديث المخزون: ' . implode(', ', $batchResult['errors'])
                ], 422);
            }
            
            // Calculate invoice total
            $invoice->subtotal = $subtotal;
            $invoice->profit = $profit;
            
            // Apply invoice-level discount if any
            $total = $subtotal;
            if ($invoice->discount_percentage > 0) {
                $invoice->discount_value = $subtotal * ($invoice->discount_percentage / 100);
                $total = $subtotal - $invoice->discount_value;
            } elseif ($invoice->discount_value > 0) {
                $total = $subtotal - $invoice->discount_value;
                // Calculate the equivalent percentage
                if ($subtotal > 0) {
                    $invoice->discount_percentage = ($invoice->discount_value / $subtotal) * 100;
                }
            }
            
            $invoice->total = $total;
            $invoice->remaining_amount = $total - $invoice->paid_amount;
            
            // Update payment status based on remaining amount
            if ($invoice->remaining_amount <= 0) {
                $invoice->payment_status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->payment_status = 'partially_paid';
            } else {
                $invoice->payment_status = 'unpaid';
            }

            $invoice->save();

            // ======== Mixed Payment Handling ========
            if ($invoice->type === 'mixed') {
                $payments = $invoiceData['payments'] ?? $request->input('payments', []);

                // Validation: at least two payment legs and correct total
                if (!is_array($payments) || count($payments) < 2) {
                    throw new \Exception('يجب إدخال طريقتي دفع على الأقل للفاتورة متعددة الدفعات');
                }

                $totalPaid = 0;
                foreach ($payments as $payment) {
                    // Basic structure validation
                    if (!isset($payment['method'], $payment['amount'])) {
                        throw new \Exception('كل دفعة يجب أن تحتوي على الحقلين method و amount');
                    }
                    $method    = $payment['method'];
                    $amount    = (float) $payment['amount'];
                    $reference = $payment['reference'] ?? null;

                    if ($amount <= 0) {
                        throw new \Exception('قيمة الدفعة يجب أن تكون أكبر من صفر');
                    }

                    $totalPaid += $amount;

                    // سجل الدفعة في جدول invoice_payments
                    $invoice->payments()->create([
                        'method'    => $method,
                        'amount'    => $amount,
                        'reference' => $reference,
                    ]);

                    // إذا كانت الدفعة من نوع "آجل" نضيفها إلى مديونية العميل
                    if ($method === 'credit' && $invoice->customer) {
                        // رصيد العميل السالب يمثل مديونية عليه
                        $invoice->customer->addToBalance(-$amount);
                    }
                }

                // سنضع قيمة المدفوع الإجمالية قبل حساب المجاميع
                $invoice->paid_amount = $totalPaid;
            }

            // تحديث رصيد العميل أو موظف الدليفري
            // نستخدم طريقة updateBalances لكل الفواتير لضمان التحديث الصحيح للأرصدة
            $invoice->updateBalances();
            
            // Award loyalty points for completed invoices
            if ($invoice->status === 'completed' && $invoice->customer_id && $invoice->customer_id != 1) {
                try {
                    $loyaltyService = app(LoyaltyService::class);
                    $pointsAwarded = $loyaltyService->awardPointsForInvoice($invoice);
                    
                    if ($pointsAwarded > 0) {
                        Log::info('منح نقاط الولاء للعميل', [
                            'customer_id' => $invoice->customer_id,
                            'invoice_id' => $invoice->id,
                            'points_awarded' => $pointsAwarded
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('خطأ في منح نقاط الولاء: ' . $e->getMessage(), [
                        'customer_id' => $invoice->customer_id,
                        'invoice_id' => $invoice->id
                    ]);
                }
            }
            
            // Update the shift's sales amounts in real-time
            if ($currentShift) {
                // Determine which field to update based on payment type
                switch ($invoice->type) {
                    case 'cash':
                        $currentShift->cash_sales += $invoice->total;
                        break;
                    case 'visa':
                        $currentShift->visa_sales += $invoice->total;
                        break;
                    case 'card':
                        $currentShift->card_sales += $invoice->total;
                        break;
                    case 'transfer':
                        $currentShift->bank_transfer_sales += $invoice->total;
                        break;
                    case 'bank':
                        $currentShift->bank_transfer_sales += $invoice->total;
                        break;
                    case 'wallet':
                        $currentShift->wallet_sales += $invoice->total;
                        break;
                }
                
                // Update the expected balance
                // Formula: opening_balance + cash_sales - withdrawals - returns
                $currentShift->expected_closing_balance = 
                    $currentShift->opening_balance + 
                    $currentShift->cash_sales - 
                    $currentShift->withdrawal_amount - 
                    $currentShift->returns_amount;

                // Save the updated shift
                $currentShift->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الفاتورة بنجاح',
                'invoice' => $invoice->load('items.product', 'customer')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate a unique invoice number
     */
    private function generateInvoiceNumber()
    {
        $prefix = date('Ymd');
        $lastInvoice = \App\Models\Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
            
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, 8);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * الحصول على رقم الفاتورة التسلسلي في الوردية الحالية
     * 
     * @return int
     */
    private function getShiftInvoiceNumber()
    {
        $currentShift = \App\Models\Shift::getCurrentOpenShift();
        
        if (!$currentShift) {
            return 1; // إذا لم تكن هناك وردية مفتوحة، نبدأ من 1
        }
        
        // الحصول على آخر فاتورة في الوردية الحالية
        $lastInvoice = \App\Models\Invoice::where('shift_id', $currentShift->id)
            ->orderBy('shift_invoice_number', 'desc')
            ->first();
            
        if ($lastInvoice && $lastInvoice->shift_invoice_number) {
            return $lastInvoice->shift_invoice_number + 1;
        }
        
        return 1; // أول فاتورة في الوردية
    }
    
    /**
     * Get the cost per unit for a product
     */
    private function getProductCostPerUnit($product, $unitId)
    {
        try {
            // Get the unit and its conversion factor
            $unit = Unit::findOrFail($unitId);
            $conversionFactor = 1; // Default for base unit
            
            if (!$unit->is_base_unit) {
                $conversionFactor = $unit->conversion_factor;
                
                // If this unit has a parent unit, multiply by parent's conversion factor
                if ($unit->parent_unit_id) {
                    $parentUnit = Unit::find($unit->parent_unit_id);
                    if ($parentUnit) {
                        $conversionFactor *= $parentUnit->conversion_factor;
                    }
                }
            }
            
            // Get the latest purchase price
            $latestPurchase = DB::table('purchase_items')
                ->where('product_id', $product->id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            if (!$latestPurchase) {
                return 0; // No purchase history, assume zero cost
            }
            
            // Get purchase unit conversion factor
            $purchaseUnit = Unit::find($latestPurchase->unit_id);
            if (!$purchaseUnit) {
                return 0;
            }
            
            $purchaseConversionFactor = 1;
            if (!$purchaseUnit->is_base_unit) {
                $purchaseConversionFactor = $purchaseUnit->conversion_factor;
                
                if ($purchaseUnit->parent_unit_id) {
                    $purchaseParentUnit = Unit::find($purchaseUnit->parent_unit_id);
                    if ($purchaseParentUnit) {
                        $purchaseConversionFactor *= $purchaseParentUnit->conversion_factor;
                    }
                }
            }
            
            // Calculate cost per base unit
            $costPerBaseUnit = $latestPurchase->purchase_price / $purchaseConversionFactor;
            
            // Calculate cost for the requested unit
            return $costPerBaseUnit * $conversionFactor;
            
        } catch (\Exception $e) {
            return 0; // Return zero if there's an error
        }
    }
    
    /**
     * Print an invoice
     */
    public function printInvoice(\App\Models\Invoice $invoice)
    {
        // Load important relationships for invoice printing
        $invoice->load(['customer', 'items.product', 'items.productUnit.unit', 'user', 'shift']);
        
        // Get the selected print layout from settings, default to layout_1 if not set
        $layout = \App\Models\Setting::get('print_layout', 'layout_1');
        
        return view("sales.print_{$layout}", compact('invoice'));
    }

    /**
     * عرض صفحة نقطة البيع
     */
    public function pos()
    {
        $currentShift = \App\Models\Shift::getCurrentOpenShift();
        $noOpenShift = !$currentShift;
        // Reuse the same optimized eager loading from index method
        $categories = Category::with([
            'products' => function($query) {
                $query->active()
                    ->select(['id', 'name', 'barcode', 'category_id', 'stock_quantity', 'is_active', 'image'])
                    ->with([
                        'units' => function($q) {
                            $q->active()
                              ->select(['id', 'product_id', 'unit_id', 'barcode', 'is_active'])
                              ->with([
                                  'unit' => function($uq) {
                                      $uq->select(['id', 'name', 'is_base_unit', 'parent_unit_id', 'conversion_factor']);
                                  },
                                  'prices' => function($pq) {
                                      $pq->with('priceType');
                                  }
                              ]);
                        },
                        'mainUnit' => function($q) {
                            $q->select(['id', 'name', 'is_base_unit', 'parent_unit_id', 'conversion_factor']);
                        }
                    ]);
            }
        ])
        ->select(['id', 'name', 'is_active'])
        ->active()
        ->get();

        // Only select needed fields from customers and employees
        $customers = Customer::active()
            ->select(['id', 'name', 'phone', 'address', 'payment_type', 'credit_balance', 'credit_limit'])
            ->orderBy('name')
            ->get();
            
        $employees = Employee::active()
            ->select(['id', 'name', 'job_title_id', 'phone', 'is_active'])
            ->orderBy('name')
            ->get();

        $priceTypes = \App\Models\PriceType::active()->orderBy('sort_order')->get();
        $allowNegativeInventory = \App\Models\Setting::get('allow_negative_inventory', false);
        $subtractInventoryOnZero = \App\Models\Setting::get('subtract_inventory_on_zero', false);

        return view('sales.index', compact('categories', 'customers', 'employees', 'noOpenShift', 'priceTypes', 'allowNegativeInventory', 'subtractInventoryOnZero', 'currentShift'));
    }

    /**
     * Get product details for sales
     */
    public function getProductDetails(Product $product)
    {
        try {
            // Load all necessary data at once to minimize DB queries
            $product->load([
                'category:id,name',
                'units' => function($q) {
                    $q->with([
                        'unit',
                        'prices.priceType',
                        'barcodes' // Eager load barcodes for each unit
                    ]);
                }
            ]);
            
            // Get price types for product listing
            $priceTypes = \App\Models\PriceType::active()->orderBy('sort_order')->get();
            
            // Find the latest purchase for cost calculation
            $latestPurchase = DB::table('purchase_items')
                ->where('product_id', $product->id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            // Initialize cost per unit
            $baseCostPerUnit = 0;
            
            // Calculate cost per unit if purchase data available
            if ($latestPurchase) {
                // Handle case when is_base_unit might not be available in the result
                $isBaseUnit = property_exists($latestPurchase, 'is_base_unit') ? $latestPurchase->is_base_unit : true;
                $conversionFactor = property_exists($latestPurchase, 'conversion_factor') ? $latestPurchase->conversion_factor : 1;
                
                $purchaseUnitPieces = $isBaseUnit ? 1 : $conversionFactor;
                
                if (!$isBaseUnit && property_exists($latestPurchase, 'parent_unit_id') && $latestPurchase->parent_unit_id) {
                    // Cache parent units for better performance
                    static $parentUnits = [];
                    
                    if (!isset($parentUnits[$latestPurchase->parent_unit_id])) {
                        $parentUnits[$latestPurchase->parent_unit_id] = DB::table('units')
                            ->select('conversion_factor')
                            ->where('id', $latestPurchase->parent_unit_id)
                            ->first();
                    }
                    
                    $parentUnit = $parentUnits[$latestPurchase->parent_unit_id];
                    if ($parentUnit) {
                        $purchaseUnitPieces *= $parentUnit->conversion_factor;
                    }
                }
                
                // Calculate cost per base unit
                $baseCostPerUnit = $latestPurchase->purchase_price / $purchaseUnitPieces;
            }

            // Preprocess all units in a single pass to avoid redundant calculations
            $unitsWithCost = [];
            $allUnits = [];
            
            // Create a map of all unit IDs for faster lookups
            foreach ($product->units as $productUnit) {
                if (!$productUnit->unit) continue;
                $allUnits[$productUnit->unit_id] = $productUnit->unit;
            }
            
            // Cache parent units for faster lookup
            $parentUnitCache = [];
            
            foreach ($product->units as $productUnit) {
                $unit = $productUnit->unit;
                if (!$unit) continue;
                
                // The $productUnit itself (instance of App\Models\ProductUnit) 
                // should have the specific cost for this product-unit combination.
                // This cost is updated by PurchaseController::updateAllUnitsCost.
                $costPerUnit = $productUnit->cost ?? 0;

                // Calculate conversion factor efficiently (still needed for stock and info)
                $conversionFactor = $unit->is_base_unit ? 1 : ($unit->conversion_factor ?? 1);
                $currentPiecesInUnit = $conversionFactor; // For clarity
                
                if (!$unit->is_base_unit && $unit->parent_unit_id) {
                    if (!isset($parentUnitCache[$unit->parent_unit_id])) {
                        if (isset($allUnits[$unit->parent_unit_id])) {
                            $parentUnitCache[$unit->parent_unit_id] = $allUnits[$unit->parent_unit_id];
                        } else {
                            // Fallback if not preloaded, though $allUnits should ideally cover it
                            $parentUnitCache[$unit->parent_unit_id] = DB::table('units')
                                ->select('id', 'name', 'conversion_factor', 'is_base_unit')
                                ->where('id', $unit->parent_unit_id)
                                ->first();
                        }
                    }
                    
                    $parentUnit = $parentUnitCache[$unit->parent_unit_id];
                    if ($parentUnit && isset($parentUnit->conversion_factor)) {
                        $currentPiecesInUnit *= $parentUnit->conversion_factor;
                    }
                }

                // Calculate cost per base unit (piece_cost) for informational purposes if needed
                // This uses the specific cost of the current unit and its pieces.
                $baseCostPerPieceForThisUnit = ($currentPiecesInUnit > 0) ? ($costPerUnit / $currentPiecesInUnit) : 0;
                
                // Format price data for each price type
                $priceData = [];
                foreach ($priceTypes as $priceType) {
                    $priceValue = $this->getUnitPrice($productUnit, $priceType->code);
                    // Create an object for each price type, matching the structure
                    // expected by the frontend and the ProductUnit::toArray() method.
                    $priceData[] = [
                        'price_type_id' => $priceType->id,
                        'price_type_name' => $priceType->name,
                        'price_type_code' => $priceType->code,
                        'value' => $priceValue,
                        'is_default' => $priceType->is_default
                    ];
                }
                
                // Format unit information
                $conversionInfo = $unit->is_base_unit 
                    ? 'وحدة رئيسية' 
                    : sprintf(
                        '1 %s = %s قطعة',
                        $unit->name,
                        $conversionFactor
                    );

                $unitData = [
                    'id' => $productUnit->id,
                    'unit_id' => $unit->id,
                    'name' => $unit->name,
                    'barcodes' => $productUnit->barcodes->pluck('barcode'), // Pass all barcodes
                    'cost' => $costPerUnit,
                    'is_main_unit' => $productUnit->is_main_unit,
                    'conversion_factor' => $currentPiecesInUnit,
                    'conversion_info' => $conversionInfo,
                    'stock' => floor($product->stock_quantity / $conversionFactor),
                    'prices' => $priceData
                ];
                
                $unitsWithCost[] = $unitData;
            }

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'stock_quantity' => $product->stock_quantity,
                    'main_unit_id' => $product->main_unit_id,
                    'units' => $unitsWithCost,
                    'price_types' => $priceTypes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب بيانات المنتج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update inventory for a single product (uses batch processing for efficiency)
     */
    private function updateInventory($productId, $unitId, $quantity)
    {
        // Get the ProductUnit to find the actual unit_id from units table
        $productUnit = ProductUnit::find($unitId);
        if (!$productUnit) {
            return [
                'success' => false,
                'errors' => ["ProductUnit not found for ID: {$unitId}"]
            ];
        }
        
        $unit = $productUnit->unit;
        if (!$unit) {
            return [
                'success' => false,
                'errors' => ["Unit not found for ProductUnit ID: {$unitId}"]
            ];
        }
        
        $conversionFactor = 1;
        if (!$unit->is_base_unit) {
            $conversionFactor = $unit->conversion_factor;
            if ($unit->parent_unit_id) {
                $parentUnit = \App\Models\Unit::find($unit->parent_unit_id);
                if ($parentUnit) {
                    $conversionFactor *= $parentUnit->conversion_factor;
                }
            }
        }
        
        // Use the batch update method for better performance
        return $this->batchUpdateInventory([
            [
                'product_id' => $productId,
                'unit_id' => $unit->id, // Use the actual units.id, not product_units.id
                'product_unit_id' => $unitId, // Keep track of the product_unit for reference
                'quantity' => $quantity,
                'conversion_factor' => $conversionFactor,
                'invoice_id' => request()->session()->get('current_invoice_id', 0)
            ]
        ]);
    }

    /**
     * Helper method to get the unit price based on price type
     *
     * @param \App\Models\ProductUnit $productUnit
     * @param string $priceType
     * @return float
     */
    private function getUnitPrice($productUnit, $priceType = null)
    {
        // Ensure prices are loaded
        if (!$productUnit->relationLoaded('prices')) {
            $productUnit->load('prices.priceType');
        }
        
        $prices = $productUnit->prices;
        
        if (empty($priceType)) {
            // If no price type specified, get default
            $price = $prices->first(function ($price) {
                return $price->priceType && $price->priceType->is_default;
            });
            
            if ($price) {
                return $price->value;
            }
        } else {
            // Find matching price by price type code
            $price = $prices->first(function ($price) use ($priceType) {
                return $price->priceType && $price->priceType->code === $priceType;
            });
            
            if ($price) {
                return $price->value;
            }
            
            // For backward compatibility with old price type strings
            if (in_array($priceType, ['retail', 'wholesale', 'distributor'])) {
                $compatibilityCode = 'main_price'; // Default for 'retail'
                
                if ($priceType === 'wholesale') {
                    $compatibilityCode = 'price_2';
                } elseif ($priceType === 'distributor') {
                    $compatibilityCode = 'price_3';
                }
                
                // Try again with the mapped code
                $price = $prices->first(function ($price) use ($compatibilityCode) {
                    return $price->priceType && $price->priceType->code === $compatibilityCode;
                });
                
                if ($price) {
                    return $price->value;
                }
            }
        }
        
        // If still no price found, get the default price
        $price = $prices->first(function ($price) {
            return $price->priceType && $price->priceType->is_default;
        });
        
        // Return the price value or 0 if none found
        return $price ? $price->value : 0;
    }

    /**
     * Get products for a specific category (dedicated endpoint)
     * 
     * @param int $categoryId
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductsByCategory($categoryId, Request $request)
    {
        try {
            // Build optimized query with eager loading
            $query = Product::with([
                'category:id,name,color',
                'units' => function($q) {
                    $q->with([
                        'unit:id,name,is_base_unit,conversion_factor,parent_unit_id',
                        'prices.priceType'
                    ]);
                },
                'mainUnit:id,name,is_base_unit,conversion_factor'
            ])
            ->select(['id', 'name', 'barcode', 'image', 'stock_quantity', 'category_id', 'main_unit_id', 'is_active'])
            ->where('category_id', $categoryId)
            ->active();
            
            // Get products
            $products = $query->get();
            
            // Get price type from request
            $priceType = $request->input('price_type', 'retail');
            
            // Format results for response
            $formattedProducts = $products->map(function($product) use ($priceType) {
                // Find the main product unit
                $mainProductUnit = $product->units->firstWhere('unit_id', $product->main_unit_id);
                
                // Get the price for the main unit based on the selected price type
                $mainUnitPrice = 0;
                if ($mainProductUnit) {
                    $mainUnitPrice = $this->getUnitPrice($mainProductUnit, $priceType);
                }
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'image_url' => $product->image ? url('/storage/products/' . $product->image) : null,
                    'stock_quantity' => $product->stock_quantity,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'color' => $product->category->color ?? '#6c757d'
                    ] : null,
                    'unit' => $product->mainUnit ? $product->mainUnit->name : null,
                    'price' => $mainUnitPrice
                ];
            });
            
            return response()->json([
                'success' => true,
                'products' => $formattedProducts,
                'multiple' => true
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل المنتجات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the current invoice number in the current shift
     */
    public function getCurrentShiftInvoiceNumber()
    {
        try {
            // تسجيل للأغراض التشخيصية
            \Log::debug('Fetching current invoice number - simplified version');
            
            // البحث مباشرة عن وردية مفتوحة دون الاعتماد على getCurrentOpenShift
            $currentShift = \App\Models\Shift::where('is_closed', false)->latest()->first();
            
            // إرجاع قيم ثابتة للتأكد من أن المسار يعمل
            $prefix = date('Ymd');
            $nextInvoiceNumber = $prefix . '0001';
            $nextShiftInvoiceNumber = 1;
            
            if ($currentShift) {
                // الحصول على آخر فاتورة في الوردية الحالية
                $lastInvoice = \App\Models\Invoice::where('shift_id', $currentShift->id)
                    ->orderBy('id', 'desc')
                    ->first();
                
                // الحصول على عدد الفواتير في الوردية الحالية
                $invoiceCount = \App\Models\Invoice::where('shift_id', $currentShift->id)->count();
                
                $nextNumber = 1;
                $nextShiftInvoiceNumber = 1;
                
                if ($lastInvoice) {
                    // استخراج الرقم التسلسلي من رقم الفاتورة
                    $lastNumber = (int) substr($lastInvoice->invoice_number, 8);
                    $nextNumber = $lastNumber + 1;
                    $nextInvoiceNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                    
                    // الحصول على رقم الفاتورة التسلسلي في الوردية
                    $nextShiftInvoiceNumber = ($lastInvoice->shift_invoice_number ?? 0) + 1;
                    // التأكد من أن الرقم لا يقل عن 1
                    $nextShiftInvoiceNumber = max(1, $nextShiftInvoiceNumber);
                }
                
                \Log::debug('Found shift and invoices', [
                    'shift_id' => $currentShift->id,
                    'invoices_count' => $invoiceCount,
                    'next_invoice' => $nextInvoiceNumber,
                    'next_shift_invoice' => $nextShiftInvoiceNumber
                ]);
                
                // إرجاع البيانات الحقيقية
                return response()->json([
                    'success' => true,
                    'current_shift_id' => $currentShift->id,
                    'current_shift_name' => $currentShift->name ?? 'وردية حالية',
                    'invoice_count' => $invoiceCount,
                    'last_invoice' => $lastInvoice ? [
                        'id' => $lastInvoice->id,
                        'invoice_number' => $lastInvoice->invoice_number,
                        'shift_invoice_number' => $lastInvoice->shift_invoice_number ?? 0
                    ] : null,
                    'next_invoice_number' => $nextInvoiceNumber,
                    'next_shift_invoice_number' => $nextShiftInvoiceNumber,
                    'reference_number' => $nextInvoiceNumber, // الرقم المرجعي (الرقم القديم)
                    'invoice_number' => $nextShiftInvoiceNumber // رقم الفاتورة الجديد في الوردية
                ]);
            }
            
            // إرجاع قيم ثابتة في حالة عدم وجود وردية
            \Log::debug('Returning fixed test values - no shift found');
            return response()->json([
                'success' => true,
                'message' => 'قيم اختبار ثابتة',
                'invoice_count' => 5,
                'next_invoice_number' => $nextInvoiceNumber,
                'next_shift_invoice_number' => $nextShiftInvoiceNumber,
                'reference_number' => $nextInvoiceNumber, // الرقم المرجعي (الرقم القديم)
                'invoice_number' => $nextShiftInvoiceNumber // رقم الفاتورة الجديد في الوردية
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getCurrentShiftInvoiceNumber', ['error' => $e->getMessage()]);
            
            // إرجاع قيم ثابتة في حالة حدوث خطأ
            return response()->json([
                'success' => true,
                'message' => 'قيم ثابتة بسبب خطأ',
                'invoice_count' => 2,
                'next_invoice_number' => date('Ymd') . '0001',
                'next_shift_invoice_number' => 1,
                'reference_number' => date('Ymd') . '0001', // الرقم المرجعي (الرقم القديم)
                'invoice_number' => 1 // رقم الفاتورة الجديد في الوردية
            ]);
        }
    }

    /**
     * Return all invoices in current open shift (with customer) for POS dialog
     */
    public function getCurrentShiftInvoices()
    {
        try {
            $shift = \App\Models\Shift::getCurrentOpenShift();
            if (!$shift) {
                return response()->json(['success' => false, 'message' => 'لا توجد وردية مفتوحة حالياً'], 404);
            }

            $invoices = \App\Models\Invoice::with('customer')
                ->where('shift_id', $shift->id)
                ->latest()->get(['id','invoice_number','customer_id','total']);

            return response()->json([
                'success' => true,
                'shift_number' => $shift->shift_number,
                'invoices' => $invoices,
            ]);
        } catch (\Exception $e) {
            \Log::error('getCurrentShiftInvoices error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'خطأ في جلب الفواتير'], 500);
        }
    }
} 