<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\ReturnItem;
use App\Services\LoyaltyService;

class SalesApiController extends Controller
{
    /**
     * البحث عن المنتجات
     */
    public function searchProducts(Request $request)
    {
        try {
            $search = $request->input('search');
            $barcode = $request->input('barcode');

            if (empty($search) && empty($barcode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب توفير مصطلح بحث أو باركود'
                ], 400);
            }

            $query = Product::with(['category', 'mainUnit']);

            if (!empty($barcode)) {
                // بحث بالباركود
                $products = $query->where('barcode', $barcode)->get();
                
                // Always return a products array for consistency
                return response()->json([
                    'success' => true,
                    'multiple' => $products->count() > 1,
                    'products' => $products
                ]);
            } else {
                // بحث بالاسم
                $products = $query->where('name', 'like', '%' . $search . '%')
                                ->orWhere('barcode', 'like', '%' . $search . '%')
                                ->get();
            }

            return response()->json([
                'success' => true,
                'multiple' => true,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في البحث عن المنتجات: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن المنتجات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على تفاصيل منتج
     */
    public function getProduct($id)
    {
        try {
            $product = Product::with(['category', 'units'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على تفاصيل المنتج: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود أو حدث خطأ',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * الحصول على وحدات المنتج وأسعارها
     */
    public function getProductUnits($id, Request $request)
    {
        try {
            $priceType = $request->input('price_type', 'retail');
            $product = Product::findOrFail($id);
            
            $units = DB::table('product_units')
                ->where('product_id', $id)
                ->get();
            
            $unitsWithPricesAndStock = $units->map(function ($unit) use ($priceType, $product) {
                $priceField = 'price';
                switch ($priceType) {
                    case 'wholesale':
                        $priceField = 'wholesale_price';
                        break;
                    case 'distributor':
                        $priceField = 'distributor_price';
                        break;
                }
                
                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'price' => $unit->$priceField,
                    'cost' => $unit->cost,
                    'stock' => $product->stock_quantity * $unit->conversion_factor,
                    'conversion_factor' => $unit->conversion_factor,
                    'conversion_info' => "1 {$unit->name} = {$unit->conversion_factor} {$product->main_unit}"
                ];
            });
            
            return response()->json([
                'success' => true,
                'units' => $unitsWithPricesAndStock
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على وحدات المنتج: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود أو حدث خطأ',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * الحصول على قائمة المنتجات
     */
    public function getProducts(Request $request)
    {
        try {
            $query = Product::with(['category']);
            
            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }
            
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('barcode', 'like', '%' . $search . '%');
                });
            }
            
            $products = $query->get();
            
            return response()->json([
                'success' => true,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على المنتجات: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحصول على المنتجات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على قائمة الفئات
     */
    public function getCategories()
    {
        try {
            $categories = Category::all();
            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على الفئات: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحصول على الفئات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على قائمة العملاء
     */
    public function getCustomers()
    {
        try {
            $customers = Customer::all();
            return response()->json([
                'success' => true,
                'customers' => $customers
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على العملاء: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحصول على العملاء',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء عميل جديد
     */
    public function createCustomer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صالحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = Customer::create([
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
                'credit_balance' => 0
            ]);

            return response()->json([
                'success' => true,
                'customer' => $customer
            ], 201);
        } catch (\Exception $e) {
            Log::error('خطأ في إنشاء عميل جديد: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء عميل جديد',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على قائمة موظفي التوصيل
     */
    public function getDeliveryEmployees()
    {
        try {
            $employees = Employee::where('is_delivery', true)->get();
            return response()->json([
                'success' => true,
                'employees' => $employees
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على موظفي التوصيل: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحصول على موظفي التوصيل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء فاتورة جديدة
     */
    public function storeInvoice(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'invoice.invoice_type' => 'required|in:cash,credit',
                'invoice.order_type' => 'required|in:takeaway,delivery',
                'invoice.customer_id' => 'required|exists:customers,id',
                'invoice.paid_amount' => 'required|numeric|min:0',
                'invoice.discount_value' => 'required|numeric|min:0',
                'invoice.discount_percentage' => 'required|numeric|min:0|max:100',
                'invoice.items' => 'required|array|min:1',
                'invoice.items.*.product_id' => 'required|exists:products,id',
                'invoice.items.*.unit_id' => 'required|exists:product_units,id',
                'invoice.items.*.quantity' => 'required|numeric|min:0.01',
                'invoice.items.*.unit_price' => 'required|numeric|min:0',
                'invoice.items.*.discount_value' => 'required|numeric|min:0',
                'invoice.items.*.discount_percentage' => 'required|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صالحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoiceData = $request->input('invoice');
            
            // التحقق من نوع الطلب
            if ($invoiceData['order_type'] === 'delivery' && empty($invoiceData['delivery_employee_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تحديد موظف التوصيل لطلبات التوصيل'
                ], 422);
            }

            // إنشاء رقم الفاتورة
            $invoiceNumber = $this->generateInvoiceNumber();

            // حساب المبالغ الإجمالية للفاتورة
            $subtotal = 0;
            $totalDiscount = 0;

            foreach ($invoiceData['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = 0;

                if ($item['discount_percentage'] > 0) {
                    $itemDiscount = $itemTotal * ($item['discount_percentage'] / 100);
                } else {
                    $itemDiscount = $item['discount_value'];
                }

                $subtotal += $itemTotal;
                $totalDiscount += $itemDiscount;
            }

            // إضافة خصم الفاتورة
            $invoiceDiscount = 0;
            if ($invoiceData['discount_percentage'] > 0) {
                $invoiceDiscount = $subtotal * ($invoiceData['discount_percentage'] / 100);
            } else {
                $invoiceDiscount = $invoiceData['discount_value'];
            }
            $totalDiscount += $invoiceDiscount;

            // المبلغ النهائي بعد الخصم
            $total = $subtotal - $totalDiscount;
            
            // المبلغ المتبقي
            $remaining = $total - $invoiceData['paid_amount'];
            
            // إذا كانت فاتورة كاش، يجب دفع المبلغ كاملاً
            if ($invoiceData['invoice_type'] === 'cash' && $remaining > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب دفع المبلغ كاملاً للفواتير النقدية'
                ], 422);
            }

            // إنشاء الفاتورة
            $invoice = new Invoice();
            $invoice->invoice_number = $invoiceNumber;
            $invoice->type = $invoiceData['invoice_type'];
            $invoice->order_type = $invoiceData['order_type'];
            $invoice->customer_id = $invoiceData['customer_id'];
            $invoice->subtotal = $subtotal;
            $invoice->discount_value = $invoiceData['discount_value'];
            $invoice->discount_percentage = $invoiceData['discount_percentage'];
            if (in_array('total_discount', (new Invoice())->getFillable())) {
                $invoice->total_discount = $totalDiscount;
            }
            $invoice->total = $total;
            $invoice->paid_amount = $invoiceData['paid_amount'];
            $invoice->remaining_amount = $remaining;
            $invoice->price_type = $invoiceData['price_type'] ?? 'retail';
            $invoice->delivery_employee_id = $invoiceData['delivery_employee_id'] ?? null;
            $invoice->status = $invoiceData['invoice_type'] === 'cash' ? 'completed' : 'pending';
            $invoice->save();

            // Prepare collections for batch processing
            $invoiceItemsData = [];
            $inventoryOperations = [];
            $now = now();

            // حفظ عناصر الفاتورة وتحديث المخزون
            foreach ($invoiceData['items'] as $itemRequest) {
                // $itemRequest['unit_id'] is now the actual product_units.id from the frontend
                // Fetch the ProductUnit by its ID to get conversion_factor and verify product linkage
                $productUnit = ProductUnit::find($itemRequest['unit_id']);

                if (!$productUnit || $productUnit->product_id != $itemRequest['product_id']) {
                    DB::rollBack();
                    Log::error("Invalid ProductUnit association in storeInvoice.", [
                        'requested_product_id' => $itemRequest['product_id'],
                        'requested_product_unit_id' => $itemRequest['unit_id'],
                        'found_product_unit' => $productUnit ? $productUnit->toArray() : null
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => "Product unit ID " . $itemRequest['unit_id'] . " is not valid for Product ID " . $itemRequest['product_id'] . "."
                    ], 422);
                }

                $itemTotal = $itemRequest['quantity'] * $itemRequest['unit_price'];
                $itemDiscount = 0;
                if (($itemRequest['discount_percentage'] ?? 0) > 0) {
                    $itemDiscount = $itemTotal * ($itemRequest['discount_percentage'] / 100);
                } else {
                    $itemDiscount = $itemRequest['discount_value'] ?? 0;
                }
                $finalPrice = $itemTotal - $itemDiscount;

                $invoiceItemsData[] = [
                    'invoice_id' => $invoice->id,
                    'product_id' => $itemRequest['product_id'],
                    'unit_id' => $itemRequest['unit_id'],
                    'quantity' => $itemRequest['quantity'],
                    'unit_price' => $itemRequest['unit_price'],
                    'discount_percentage' => $itemRequest['discount_percentage'] ?? 0,
                    'discount_value' => $itemRequest['discount_value'] ?? 0,
                    'total_discount' => $itemDiscount,
                    'subtotal' => $itemTotal,
                    'total' => $finalPrice,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                $inventoryOperations[] = [
                    'product_id' => $itemRequest['product_id'],
                    'unit_id' => $productUnit->unit_id,
                    'product_unit_id' => $productUnit->id,
                    'quantity' => $itemRequest['quantity'],
                    'conversion_factor' => $productUnit->conversion_factor,
                    'invoice_id' => $invoice->id
                ];
            }

            // Perform batch inserts
            InvoiceItem::insert($invoiceItemsData);
            
            // Process inventory updates in batch
            app(\App\Http\Controllers\SalesController::class)->batchUpdateInventory($inventoryOperations);

            // تحديث رصيد العميل إذا كانت فاتورة آجلة أو دليفري
            // نلاحظ: نستخدم طريقة updateBalances لجميع الفواتير لضمان تحديث الرصيد بشكل صحيح
            // هذه الطريقة ستتحقق من نوع الفاتورة داخليًا وتتصرف بناءً على ذلك
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
            
            // تحديث إحصائيات الوردية الحالية إن وجدت
            $currentShift = \App\Models\Shift::getCurrentOpenShift();
            if ($currentShift) {
                // Add the shift_id to the invoice
                $invoice->shift_id = $currentShift->id;
                $invoice->save();
                
                // تحديث إحصائيات الوردية بناءً على نوع الفاتورة
                // استخدام الحقل المُخزن في الفاتورة (type) بدلاً من المتغير في البيانات المرسلة
                switch ($invoice->type) {
                    case 'cash':
                        $currentShift->cash_sales += $total;
                        break;
                    case 'card':
                        $currentShift->card_sales += $total;
                        break;
                    case 'bank':
                        $currentShift->bank_transfer_sales += $total;
                        break;
                    case 'wallet':
                        $currentShift->wallet_sales += $total;
                        break;
                }
                
                // تحديث الرصيد المتوقع
                // المعادلة: الرصيد الافتتاحي + المبيعات النقدية - المسحوبات - المرتجعات
                $currentShift->expected_closing_balance = 
                    $currentShift->opening_balance + 
                    $currentShift->cash_sales - 
                    $currentShift->withdrawal_amount - 
                    $currentShift->returns_amount;
                
                // حفظ الوردية بعد التحديث
                $currentShift->save();
                
                // تسجيل لوغ للتحديث
                \Illuminate\Support\Facades\Log::info('تم تحديث إحصائيات الوردية', [
                    'shift_id' => $currentShift->id,
                    'cash_sales' => $currentShift->cash_sales,
                    'card_sales' => $currentShift->card_sales,
                    'bank_transfer_sales' => $currentShift->bank_transfer_sales,
                    'wallet_sales' => $currentShift->wallet_sales,
                    'expected_closing_balance' => $currentShift->expected_closing_balance
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الفاتورة بنجاح',
                'invoice' => $invoice->load('items', 'customer')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطأ في إنشاء الفاتورة: ' . $e->getMessage(), ['request' => $request->all(), 'exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على تفاصيل فاتورة
     */
    public function getInvoice(Request $request, $id)
    {
        try {
            $invoice = Invoice::with([
                'items.product', 
                'items.productUnit.unit',
                'customer',
                'user',
                'payments' // load mixed payments
            ])->find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على الفاتورة.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'invoice' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching invoice details for ID: {$id}. Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء جلب تفاصيل الفاتورة.'], 500);
        }
    }

    /**
     * الحصول على قائمة الفواتير
     */
    public function getInvoices(Request $request)
    {
        try {
            $query = Invoice::with(['customer']);
            
            // تصفية حسب العميل
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->input('customer_id'));
            }
            
            // تصفية حسب نوع الفاتورة
            if ($request->has('invoice_type')) {
                $query->where('invoice_type', $request->input('invoice_type'));
            }
            
            // تصفية حسب نوع الطلب
            if ($request->has('order_type')) {
                $query->where('order_type', $request->input('order_type'));
            }
            
            // تصفية حسب حالة الفاتورة
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }
            
            // تصفية حسب التاريخ
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }
            
            // ترتيب النتائج
            $query->orderBy('created_at', 'desc');
            
            // التقسيم إلى صفحات
            $perPage = $request->input('per_page', 15);
            $invoices = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'invoices' => $invoices
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على الفواتير: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحصول على الفواتير',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * توليد رقم فاتورة فريد
     */
    private function generateInvoiceNumber()
    {
        // الحصول على تاريخ اليوم بالتنسيق YYYYMMDD
        $today = Carbon::now()->format('Ymd');
        
        // البحث عن آخر فاتورة لهذا اليوم
        $lastInvoice = Invoice::where('invoice_number', 'like', $today . '%')
                            ->orderBy('invoice_number', 'desc')
                            ->first();
        
        if ($lastInvoice) {
            // استخراج الرقم التسلسلي من آخر فاتورة
            $lastNumber = (int) substr($lastInvoice->invoice_number, 8);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        // تنسيق الرقم التسلسلي بأربعة أرقام
        $sequentialNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        
        // إنشاء رقم الفاتورة الجديد
        return $today . $sequentialNumber;
    }

    /**
     * تحديث المخزون بعد إنشاء الفاتورة
     * Legacy method - kept for compatibility
     * For better performance, use batch processing
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
        
        $conversionFactor = $productUnit->conversion_factor ?? 1;
        
        return app(\App\Http\Controllers\SalesController::class)->batchUpdateInventory([
            [
                'product_id' => $productId,
                'unit_id' => $productUnit->unit_id, // Use the actual units.id, not product_units.id
                'product_unit_id' => $unitId, // Keep track of the product_unit for reference
                'quantity' => $quantity,
                'conversion_factor' => $conversionFactor,
                'invoice_id' => session()->get('current_invoice_id', 0)
            ]
        ]);
    }

    /**
     * Fetches and transforms an invoice specifically for the sales return page.
     * Returns a "flat" JSON structure.
     */
    public function getInvoiceByNumber($invoiceNumber)
    {
        Log::info("Attempting to find invoice for return by number: " . $invoiceNumber);

        try {
            $invoice = Invoice::with([
                'items' => function ($query) {
                    $query->with(['product:id,name', 'unit:id,name']);
                },
                'customer:id,name,phone,address'
            ])
            ->where('invoice_number', $invoiceNumber)
            ->first();

            if (!$invoice) {
                Log::warning("Invoice with number {$invoiceNumber} not found in database for return.");
                return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
            }

            Log::info("Successfully found invoice ID for return: " . $invoice->id);

            // Transform data to match the expected frontend structure for returns page
            $transformedInvoice = [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name ?? 'عميل نقدي',
                'date' => $invoice->created_at->format('Y-m-d H:i:s'),
                'items' => $invoice->items->map(function ($item) use ($invoice) {
                    try {
                        // Calculate returned quantity for this item by checking all returns associated with the original invoice
                        $returned_previously = DB::table('return_items')
                            ->join('sales_returns', 'return_items.sales_return_id', '=', 'sales_returns.id')
                            ->where('sales_returns.invoice_id', $invoice->id)
                            ->where('return_items.product_id', $item->product_id)
                            ->sum('return_items.quantity_returned');

                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'unit_id' => $item->unit_id,
                            'product_name' => $item->product->name ?? 'N/A',
                            'unit_name' => $item->unit->name ?? 'N/A',
                            'price' => $item->unit_price,
                            'quantity_sold' => $item->quantity,
                            'quantity_returned_previously' => $returned_previously,
                        ];
                    } catch (\Exception $e) {
                        Log::error("Error transforming invoice item ID {$item->id} for invoice {$item->invoice_id} for return: " . $e->getMessage());
                        return [
                            'id' => $item->id,
                            'product_name' => 'Error loading item',
                            'quantity_sold' => 0,
                            'quantity_returned_previously' => 0,
                        ];
                    }
                }),
            ];

            return response()->json(['success' => true, 'invoice' => $transformedInvoice]);

        } catch (\Exception $e) {
            Log::error("Error fetching invoice for return by number {$invoiceNumber}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }

    /**
     * Calculate loyalty points for invoice
     */
    public function calculateLoyaltyPoints(Request $request)
    {
        try {
            $customerId = $request->input('customer_id');
            $invoiceTotal = $request->input('invoice_total', 0);
            $itemsCount = $request->input('items_count', 0);
            
            if (!$customerId || $customerId == 1) {
                return response()->json([
                    'success' => true,
                    'points_to_earn' => 0,
                    'message' => 'عميل نقدي - لا يتم منح نقاط'
                ]);
            }

            $loyaltyService = app(LoyaltyService::class);
            $pointsToEarn = $loyaltyService->calculatePointsForInvoice($invoiceTotal, $itemsCount);
            
            return response()->json([
                'success' => true,
                'points_to_earn' => $pointsToEarn,
                'customer_id' => $customerId
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في حساب نقاط الولاء: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حساب النقاط',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer loyalty status
     */
    public function getCustomerLoyaltyStatus(Request $request, $customerId)
    {
        try {
            if (!$customerId || $customerId == 1) {
                return response()->json([
                    'success' => true,
                    'is_cash_customer' => true,
                    'total_points' => 0,
                    'can_redeem' => false
                ]);
            }

            $customer = Customer::find($customerId);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'العميل غير موجود'
                ], 404);
            }

            $loyaltyService = app(LoyaltyService::class);
            $loyaltyStatus = $loyaltyService->getCustomerLoyaltySummary($customerId);
            
            return response()->json([
                'success' => true,
                'is_cash_customer' => false,
                'customer_name' => $customer->name,
                'total_points' => $loyaltyStatus['total_points'],
                'can_redeem' => $loyaltyStatus['can_redeem'],
                'points_value' => $loyaltyStatus['points_value'],
                'max_discount_allowed' => $loyaltyStatus['max_discount_allowed'] ?? 0
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على حالة ولاء العميل: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في الحصول على بيانات العميل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate discount from loyalty points
     */
    public function calculatePointsDiscount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',
                'points_to_use' => 'required|integer|min:1',
                'invoice_total' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customerId = $request->input('customer_id');
            $pointsToUse = $request->input('points_to_use');
            $invoiceTotal = $request->input('invoice_total');

            if ($customerId == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استخدام النقاط للعميل النقدي'
                ], 422);
            }

            $loyaltyService = app(LoyaltyService::class);
            $discountResult = $loyaltyService->calculateDiscountFromPoints($customerId, $pointsToUse, $invoiceTotal);
            
            return response()->json([
                'success' => true,
                'discount_amount' => $discountResult['discount_amount'],
                'points_used' => $discountResult['points_used'],
                'remaining_points' => $discountResult['remaining_points'],
                'new_invoice_total' => $invoiceTotal - $discountResult['discount_amount']
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في حساب خصم النقاط: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 