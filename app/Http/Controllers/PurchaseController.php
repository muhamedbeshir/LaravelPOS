<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use PDF;
use App\Models\ProductLog;

class PurchaseController extends Controller
{
    use AuthorizesRequests;

    protected static array $middlewares = [
        'auth',
        'permission:view-purchases' => ['only' => ['index', 'show']],
        'permission:create-purchases' => ['only' => ['create', 'store']],
        'permission:edit-purchases' => ['only' => ['edit', 'update']],
        'permission:delete-purchases' => ['only' => ['destroy']],
        'permission:manage-purchase-payments' => ['only' => ['payments']],
    ];

    public function index()
    {
        $purchases = Purchase::with(['supplier', 'employee'])
            ->latest()
            ->paginate(10);
        
        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->get();
        $products = Product::with(['units' => function($query) {
            $query->with('unit')
                  ->where('is_active', true)
                  ->orderBy('is_main_unit', 'desc');
        }])->active()->get();
        $employees = Employee::active()->get();
        
        return view('purchases.create', compact('suppliers', 'products', 'employees'));
    }

    public function store(Request $request)
    {
        try {
            \Log::info('Purchase store method called with data:', $request->all());
            
            $validated = $request->validate([
                'supplier_id' => 'nullable|exists:suppliers,id',
                'employee_id' => 'nullable|exists:employees,id',
                'purchase_date' => 'required|date',
                'total_amount' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.unit_id' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $productId = $request->input("items.$index.product_id");
                        if ($productId) {
                            $exists = ProductUnit::where('id', $value)
                                                 ->where('product_id', $productId)
                                                 ->exists();
                            if (!$exists) {
                                $fail("The selected unit is not valid for the chosen product in row " . ($index + 1) . ".");
                            }
                        }
                    },
                ],
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.purchase_price' => 'required|numeric|min:0',
                'items.*.selling_price' => 'required|numeric|min:0',
                'items.*.production_date' => 'nullable|date',
                'items.*.expiry_date' => 'nullable|date|after_or_equal:items.*.production_date',
                'items.*.alert_days_before_expiry' => 'nullable|integer|min:1'
            ]);

            \Log::info('Validation passed, beginning transaction');
            DB::beginTransaction();

            $purchase = new Purchase();
            $purchase->supplier_id = $request->supplier_id;
            $purchase->employee_id = $request->employee_id;
            $purchase->total_amount = $request->total_amount;
            $purchase->paid_amount = $request->paid_amount;
            $purchase->remaining_amount = $request->total_amount - $request->paid_amount;
            $purchase->invoice_number = $purchase->generateInvoiceNumber();
            $purchase->purchase_date = $request->purchase_date;
            $purchase->notes = $request->notes;

            // تحديث حالة الفاتورة بناءً على المبلغ المدفوع والمتبقي
            if ($purchase->remaining_amount <= 0) {
                $purchase->status = 'paid';
            } elseif ($purchase->paid_amount > 0) {
                $purchase->status = 'partially_paid';
            } else {
                $purchase->status = 'pending';
            }

            // إضافة ملاحظة إذا لم يتم تحديد المورد أو الموظف
            if (!$request->supplier_id || !$request->employee_id) {
                $additionalNote = 'تم إنشاء الفاتورة ';
                if (!$request->supplier_id) {
                    $additionalNote .= 'بدون تحديد مورد ';
                }
                if (!$request->employee_id) {
                    $additionalNote .= (!$request->supplier_id ? 'و' : '') . 'بدون تحديد موظف مستلم ';
                }
                $purchase->notes = $purchase->notes 
                    ? $purchase->notes . ' | ' . $additionalNote
                    : $additionalNote;
            }

            \Log::info('Saving purchase with data:', [
                'invoice_number' => $purchase->invoice_number,
                'supplier_id' => $purchase->supplier_id,
                'employee_id' => $purchase->employee_id,
                'total_amount' => $purchase->total_amount,
                'paid_amount' => $purchase->paid_amount
            ]);
            
            $purchase->save();
            \Log::info('Purchase saved with ID: ' . $purchase->id);

            foreach ($request->items as $item) {
                ProductLog::create([
                    'product_id' => $item['product_id'],
                    'event' => 'تم شراء المنتج', // Product purchased
                    'quantity' => $item['quantity'],
                    'reference' => 'فاتورة شراء #' . $purchase->invoice_number,
                ]);
            }

            foreach ($request->items as $index => $item) {
                \Log::info('Processing item ' . ($index + 1), $item);
                
                $productUnit = ProductUnit::find($item['unit_id']);
                if (!$productUnit) {
                    DB::rollBack();
                    return back()->with('error', 'Invalid unit provided for item ' . ($index + 1));
                }
                
                $purchaseItem = new PurchaseItem();
                $purchaseItem->purchase_id = $purchase->id;
                $purchaseItem->product_id = $item['product_id'];
                $purchaseItem->unit_id = $productUnit->unit_id;
                $purchaseItem->quantity = $item['quantity'];
                $purchaseItem->purchase_price = $item['purchase_price'];
                $purchaseItem->selling_price = $item['selling_price'] ?? 0;
                $purchaseItem->production_date = $item['production_date'] ?? null;
                $purchaseItem->expiry_date = $item['expiry_date'] ?? null;
                $purchaseItem->alert_days_before_expiry = $item['alert_days_before_expiry'] ?? 30;
                $purchaseItem->calculateProfit();
                $purchaseItem->save();
                \Log::info('Purchase item saved with ID: ' . $purchaseItem->id);

                // تحديث تكلفة جميع وحدات المنتج
                $this->updateAllUnitsCost($item['product_id'], $productUnit->unit_id, $item['purchase_price']);

                // تحديث المخزون
                $product = Product::find($item['product_id']);
                try {
                    // التحقق من وجود المنتج
                    if (!$product) {
                        throw new \Exception('المنتج غير موجود برقم: ' . $item['product_id']);
                    }
                    
                    // تعيين معرف الموظف المسؤول عن تحديث المخزون
                    $employeeId = null;
                    
                    // إذا تم تحديد موظف في الطلب
                    if ($request->employee_id) {
                        $employeeId = $request->employee_id;
                    } else {
                        // البحث عن أول موظف نشط في النظام
                        $firstEmployee = Employee::where('is_active', 1)->first();
                        if ($firstEmployee) {
                            $employeeId = $firstEmployee->id;
                            \Log::info('Using first active employee for stock movement: ' . $firstEmployee->name . ' (ID: ' . $employeeId . ')');
                        } else {
                            // إنشاء موظف افتراضي إذا لم يتوفر أي موظف
                            \Log::info('No active employees found. Creating a default employee for system operations.');
                            $jobTitle = \App\Models\JobTitle::firstOrCreate(
                                ['name' => 'مدير نظام'],
                                ['is_active' => 1]
                            );
                            
                            $systemEmployee = Employee::create([
                                'name' => 'النظام',
                                'employee_number' => 'SYS001',
                                'job_title_id' => $jobTitle->id,
                                'is_active' => 1
                            ]);
                            
                            $employeeId = $systemEmployee->id;
                            \Log::info('Created system employee with ID: ' . $employeeId);
                        }
                    }
                    
                    if (!$employeeId) {
                        throw new \Exception('لا يمكن تحديث المخزون: لم يتم العثور على موظف مناسب لتسجيل حركة المخزون');
                    }
                    
                    $productName = $product->name ?? 'منتج غير معروف';
                    \Log::info('Updating stock for product: ' . $productName . ' with employee ID: ' . $employeeId);
                    $product->updateStock(
                        $item['quantity'], 
                        $productUnit->unit_id,
                        'add',
                        [
                            'reference_type' => 'App\\Models\\Purchase',
                            'reference_id' => $purchase->id,
                            'employee_id' => $employeeId,
                            'notes' => 'إضافة مخزون من فاتورة شراء رقم ' . $purchase->invoice_number
                        ]
                    );
                    \Log::info('Stock updated successfully');
                } catch (\Exception $e) {
                    \Log::error('Error updating stock: ' . $e->getMessage(), ['exception' => $e]);
                    DB::rollback();
                    
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'حدث خطأ في تحديث المخزون: ' . $e->getMessage()
                        ], 500);
                    }
                    
                    return back()->with('error', 'حدث خطأ في تحديث المخزون: ' . $e->getMessage());
                }
            }

            DB::commit();
            \Log::info('Transaction committed successfully, purchase created with ID: ' . $purchase->id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء فاتورة الشراء بنجاح',
                    'id' => $purchase->id,
                    'redirect' => route('purchases.show', $purchase)
                ]);
            }

            return redirect()->route('purchases.show', $purchase)
                ->with('success', 'تم إنشاء فاتورة الشراء بنجاح');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error: ' . $e->getMessage(), [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $e->errors()
                ], 422);
            }
            
            throw $e; // إعادة رمي الاستثناء للتعامل معه بواسطة Laravel
        } catch (\Exception $e) {
            \Log::error('Error in purchase store: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
            if (DB::transactionLevel() > 0) {
                DB::rollback();
                \Log::info('Transaction rolled back due to error');
            }
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حفظ فاتورة الشراء: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'حدث خطأ أثناء حفظ فاتورة الشراء: ' . $e->getMessage());
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

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'employee', 'items.product', 'items.unit']);
        return view('purchases.show', compact('purchase'));
    }

    public function getProfitAnalytics()
    {
        $topProfitableProducts = PurchaseItem::with('product')
            ->select('product_id', 
                DB::raw('SUM(expected_profit) as total_profit'),
                DB::raw('AVG(profit_percentage) as avg_profit_percentage'))
            ->groupBy('product_id')
            ->orderByDesc('total_profit')
            ->limit(10)
            ->get();

        return response()->json($topProfitableProducts);
    }

    public function checkExpiryDates()
    {
        $nearExpiryItems = PurchaseItem::whereNotNull('expiry_date')
            ->with(['product', 'purchase'])
            ->whereRaw('DATEDIFF(expiry_date, CURDATE()) <= alert_days_before_expiry')
            ->where('expiry_date', '>', Carbon::now())
            ->get();

        return view('purchases.expiry-alerts', compact('nearExpiryItems'));
    }

    public function exportToPdf(Purchase $purchase)
    {
        $pdf = PDF::loadView('purchases.pdf', compact('purchase'));
        return $pdf->download('purchase-' . $purchase->invoice_number . '.pdf');
    }

    public function completePayment(Purchase $purchase)
    {
        $this->authorize('manage-purchase-payments');

        if ($purchase->remaining_amount <= 0) {
            return back()->with('warning', 'الفاتورة مدفوعة بالكامل بالفعل.');
        }

        DB::beginTransaction();
        try {
            $amountToPay = $purchase->remaining_amount;
            
            $purchase->paid_amount += $amountToPay;
            $purchase->remaining_amount = 0;
            $purchase->status = 'paid'; // تحديث حالة الفاتورة إلى "مدفوعة"
            $purchase->save();

            if ($purchase->supplier) {
                // Assuming a positive balance on supplier means we owe them.
                // Paying the purchase reduces what we owe.
                $purchase->supplier->decrement('remaining_amount', $amountToPay);
            }

            DB::commit();

            return back()->with('success', 'تم إتمام عملية الدفع للفاتورة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error completing purchase payment for purchase #{$purchase->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء إتمام عملية الدفع.');
        }
    }
} 