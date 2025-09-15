<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Models\StockMovement;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseReturnController extends Controller
{
    protected static array $middlewares = [
        'auth',
        'permission:view-purchase-returns' => ['only' => ['index', 'show']],
        'permission:create-purchase-returns' => ['only' => ['create', 'store']],
        'permission:edit-purchase-returns' => ['only' => ['edit', 'update']],
        'permission:delete-purchase-returns' => ['only' => ['destroy']],
    ];
    
    /**
     * Display a listing of purchase returns
     */
    public function index(Request $request)
    {
        $query = PurchaseReturn::with(['supplier', 'employee', 'purchase']);
        
        // Apply filters
        // Search filter
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('return_number', 'like', "%{$searchTerm}%")
                  ->orWhere('notes', 'like', "%{$searchTerm}%")
                  ->orWhereHas('supplier', function($sq) use ($searchTerm) {
                      $sq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }
        
        // Supplier filter
        if ($request->has('supplier_id') && $request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        // Return type filter
        if ($request->has('return_type') && $request->return_type) {
            $query->where('return_type', $request->return_type);
        }
        
        // Date range filter
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('return_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('return_date', '<=', $request->end_date);
        }
        
        // Get suppliers for the filter dropdown
        $suppliers = Supplier::active()->orderBy('name')->get();
        
        // Get purchase returns with pagination
        $purchaseReturns = $query->latest()->paginate(10);
        
        return view('purchase-returns.index', compact('purchaseReturns', 'suppliers'));
    }
    
    /**
     * Show the form for creating a new purchase return
     */
    public function create()
    {
        $suppliers = Supplier::active()->get();
        $purchases = Purchase::latest()->take(50)->get();
        $employees = Employee::active()->get();
        $products = Product::with(['units' => function($query) {
            $query->with('unit')
                  ->where('is_active', true)
                  ->orderBy('is_main_unit', 'desc');
        }])->active()->get();
        
        return view('purchase-returns.create', compact('suppliers', 'purchases', 'employees', 'products'));
    }
    
    /**
     * Store a newly created purchase return
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'employee_id' => 'nullable|exists:employees,id',
                'purchase_id' => 'nullable|exists:purchases,id',
                'return_date' => 'required|date',
                'return_type' => 'required|in:full,partial,direct',
                'total_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.unit_id' => 'required|exists:units,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.purchase_price' => 'required|numeric|min:0',
                'items.*.reason' => 'nullable|string',
            ]);
            
            DB::beginTransaction();
            
            // Create purchase return record
            $purchaseReturn = new PurchaseReturn();
            $purchaseReturn->supplier_id = $request->supplier_id;
            $purchaseReturn->employee_id = $request->employee_id;
            $purchaseReturn->purchase_id = $request->purchase_id;
            $purchaseReturn->return_date = $request->return_date;
            $purchaseReturn->return_type = $request->return_type;
            $purchaseReturn->total_amount = $request->total_amount;
            $purchaseReturn->return_number = $purchaseReturn->generateReturnNumber();
            $purchaseReturn->notes = $request->notes;
            
            // ربط المرتجع بالوردية الحالية
            $currentShift = \App\Models\Shift::getCurrentOpenShift();
            if ($currentShift) {
                $purchaseReturn->shift_id = $currentShift->id;
            }
            
            $purchaseReturn->save();
            
            // Create return items and update inventory
            foreach ($request->items as $item) {
                $returnItem = new PurchaseReturnItem();
                $returnItem->purchase_return_id = $purchaseReturn->id;
                $returnItem->product_id = $item['product_id'];
                $returnItem->unit_id = $item['unit_id'];
                $returnItem->quantity = $item['quantity'];
                $returnItem->purchase_price = $item['purchase_price'];
                $returnItem->reason = $item['reason'] ?? null;
                $returnItem->save();
                
                // Update product stock (decrease)
                $product = Product::find($item['product_id']);
                
                // Find the unit conversion factor
                $productUnit = ProductUnit::where('product_id', $item['product_id'])
                    ->where('unit_id', $item['unit_id'])
                    ->first();
                
                if (!$productUnit) {
                    throw new \Exception('وحدة المنتج غير موجودة');
                }
                
                // Record stock movement for the returned items
                StockMovement::recordMovement([
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'movement_type' => 'out', // Decreasing stock for returns
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->id,
                    'employee_id' => $request->employee_id ?? auth()->id(),
                    'notes' => 'مرتجع مشتريات رقم: ' . $purchaseReturn->return_number
                ]);
                
                // Adjust the product stock quantity
                $quantityInMainUnit = $item['quantity'] * $productUnit->conversion_factor;
                $product->stock_quantity -= $quantityInMainUnit;
                $product->save();
            }
            
            // Update supplier balance
            $purchaseReturn->updateSupplierBalance();
            
            // Update the original purchase invoice if it exists
            if ($purchaseReturn->purchase_id) {
                $purchase = Purchase::find($purchaseReturn->purchase_id);
                if ($purchase) {
                    // Deduct return amount from purchase total and update remaining balance
                    $purchase->total_amount -= $purchaseReturn->total_amount;
                    $purchase->remaining_amount = $purchase->total_amount - $purchase->paid_amount;
                    
                    // Update status based on new remaining amount
                    if ($purchase->remaining_amount <= 0) {
                        $purchase->status = 'paid';
                    } elseif ($purchase->paid_amount > 0) {
                        $purchase->status = 'partially_paid';
                    } else {
                        $purchase->status = 'pending';
                    }
                    
                    $purchase->save();
                    
                    // If there's a supplier, update their amounts
                    if ($purchase->supplier) {
                        $purchase->supplier->updateAmounts();
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('purchase-returns.index')
                ->with('success', 'تم إنشاء مرتجع المشتريات بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the specified purchase return
     */
    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['supplier', 'employee', 'purchase', 'items.product', 'items.unit']);
        return view('purchase-returns.show', compact('purchaseReturn'));
    }
    
    /**
     * Process a full purchase return
     */
    public function returnFullPurchase(Request $request, $purchaseId)
    {
        try {
            $purchase = Purchase::with(['items.product', 'items.unit'])->findOrFail($purchaseId);
            
            DB::beginTransaction();
            
            $purchaseReturn = new PurchaseReturn();
            $purchaseReturn->supplier_id = $purchase->supplier_id;
            $purchaseReturn->employee_id = auth()->user()->employee_id ?? null;
            $purchaseReturn->purchase_id = $purchase->id;
            $purchaseReturn->return_date = now();
            $purchaseReturn->return_type = 'full';
            $purchaseReturn->total_amount = $purchase->total_amount;
            $purchaseReturn->return_number = $purchaseReturn->generateReturnNumber();
            $purchaseReturn->notes = $request->notes ?? 'مرتجع كامل للفاتورة رقم: ' . $purchase->invoice_number;
            
            // ربط المرتجع بالوردية الحالية
            $currentShift = \App\Models\Shift::getCurrentOpenShift();
            if ($currentShift) {
                $purchaseReturn->shift_id = $currentShift->id;
            }
            
            $purchaseReturn->save();
            
            // Create return items for all purchase items
            foreach ($purchase->items as $item) {
                $returnItem = new PurchaseReturnItem();
                $returnItem->purchase_return_id = $purchaseReturn->id;
                $returnItem->product_id = $item->product_id;
                $returnItem->unit_id = $item->unit_id;
                $returnItem->quantity = $item->quantity;
                $returnItem->purchase_price = $item->purchase_price;
                $returnItem->reason = $request->reason ?? 'مرتجع كامل';
                $returnItem->save();
                
                // Record stock movement for the returned items
                StockMovement::recordMovement([
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'quantity' => $item->quantity,
                    'movement_type' => 'out', // Decreasing stock for returns
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $purchaseReturn->id,
                    'employee_id' => $purchaseReturn->employee_id ?? auth()->id(),
                    'notes' => 'مرتجع كامل للفاتورة رقم: ' . $purchase->invoice_number
                ]);
                
                // Adjust the product stock quantity
                $product = Product::find($item->product_id);
                $productUnit = ProductUnit::where('product_id', $item->product_id)
                    ->where('unit_id', $item->unit_id)
                    ->first();
                
                if ($productUnit) {
                    $quantityInMainUnit = $item->quantity * $productUnit->conversion_factor;
                    $product->stock_quantity -= $quantityInMainUnit;
                    $product->save();
                }
            }
            
            // Update supplier balance
            $purchaseReturn->updateSupplierBalance();
            
            // Update the original purchase invoice
            if ($purchase) {
                // For full returns, set the purchase total to zero or handle according to business logic
                $purchase->total_amount = 0;
                $purchase->remaining_amount = $purchase->total_amount - $purchase->paid_amount;
                $purchase->status = 'paid'; // Since there's nothing left to pay
                $purchase->save();
                
                // If there's a supplier, update their amounts
                if ($purchase->supplier) {
                    $purchase->supplier->updateAmounts();
                }
            }
            
            DB::commit();
            
            return redirect()->route('purchase-returns.show', $purchaseReturn->id)
                ->with('success', 'تم إرجاع الفاتورة بالكامل بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for the purchase return
     */
    public function exportToPdf(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['supplier', 'employee', 'purchase', 'items.product', 'items.unit']);
        
        $companyName = setting('company_name', 'نظام إدارة المبيعات');
        $companyLogo = public_path('images/logo/' . setting('company_logo', 'logo.png'));
        
        $data = [
            'purchaseReturn' => $purchaseReturn,
            'companyName' => $companyName,
            'companyLogo' => $companyLogo,
            'date' => now()->format('Y-m-d')
        ];
        
        $pdf = PDF::loadView('purchase-returns.pdf', $data);
        
        $filename = 'مرتجع_مشتريات_' . $purchaseReturn->return_number . '.pdf';
        
        return $pdf->download($filename);
    }
    
    /**
     * عرض صفحة تقرير مرتجعات المشتريات
     */
    public function report(Request $request)
    {
        // تحميل البيانات بناءً على المعايير
        $query = PurchaseReturn::with(['supplier', 'employee', 'purchase', 'items.product', 'items.unit', 'shift']);

        // فلترة حسب المورد
        if ($request->has('supplier_id') && $request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        // فلترة حسب التاريخ
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('return_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('return_date', '<=', $request->end_date);
        }
        
        // فلترة حسب الوردية
        if ($request->has('shift_id') && $request->shift_id) {
            $query->where('shift_id', $request->shift_id);
        }
        
        // فرز النتائج
        $query->orderBy('created_at', 'desc');
        
        // استرجاع البيانات مع التقسيم إلى صفحات
        $purchaseReturns = $query->paginate(15);
        
        // حساب إجماليات التقرير
        $totalAmount = $query->sum('total_amount');
        $totalCount = $query->count();
        
        // استرجاع قائمة الموردين للفلترة
        $suppliers = \App\Models\Supplier::active()->get();
        
        // استرجاع قائمة الورديات المغلقة للفلترة
        $shifts = \App\Models\Shift::latest()->get();
        
        return view('purchase-returns.report', compact(
            'purchaseReturns',
            'suppliers',
            'shifts',
            'totalAmount',
            'totalCount',
            'request'
        ));
    }
} 