<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Shift;
use App\Models\ShiftWithdrawal;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Deposit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

#[\Illuminate\Routing\Controllers\Middleware('auth')]
class ShiftController extends Controller
{
    /**
     * عرض قائمة بالورديات
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $shifts = Shift::with([
            'mainCashier',
            'invoices',
            'withdrawals',
            'salesReturns'
        ])
        ->orderBy('created_at', 'desc')
        ->paginate(15);
            
        return view('shifts.index', compact('shifts'));
    }

    /**
     * عرض نموذج إنشاء وردية جديدة
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // التحقق من عدم وجود وردية مفتوحة
        $openShift = Shift::where('is_closed', false)->first();
        if ($openShift) {
            return redirect()->route('shifts.show', $openShift)
                ->with('warning', 'هناك وردية مفتوحة بالفعل. يجب إغلاق الوردية الحالية قبل فتح وردية جديدة.');
        }
        
        // جلب آخر وردية مغلقة لعرض معلوماتها
        $lastShift = Shift::where('is_closed', true)
            ->orderBy('end_time', 'desc')
            ->first();
        
        // جلب قائمة الموظفين لإضافتهم للوردية
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['cashier', 'admin']);
        })->get();
        
        return view('shifts.create', compact('lastShift', 'users'));
    }

    /**
     * تخزين وردية جديدة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'users' => 'nullable|array',
        ]);
        
        // التحقق من عدم وجود وردية مفتوحة للمستخدم الحالي
        $openShift = Shift::getCurrentOpenShift(true);
        if ($openShift) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'هناك وردية مفتوحة بالفعل. يجب إغلاق الوردية الحالية قبل فتح وردية جديدة.',
                    'existing_shift_id' => $openShift->id
                ], 422);
            }
            
            return redirect()->route('shifts.show', $openShift)
                ->with('warning', 'هناك وردية مفتوحة بالفعل. يجب إغلاق الوردية الحالية قبل فتح وردية جديدة.');
        }
        
        // إنشاء رقم فريد للوردية
        $shiftNumber = 'SH-' . date('Ymd') . '-' . rand(1000, 9999);
        
        try {
            // تسجيل معلومات التشخيص
            \Log::info("Starting shift creation", [
                'user_id' => Auth::id(),
                'opening_balance' => $request->opening_balance,
                'shift_number' => $shiftNumber
            ]);
            
            DB::beginTransaction();
            
            // إنشاء وردية جديدة
            $shift = Shift::create([
                'shift_number' => $shiftNumber,
                'start_time' => Carbon::now(),
                'opening_balance' => $request->opening_balance,
                'notes' => $request->notes,
                'is_closed' => false,
                'main_cashier_id' => Auth::id(),
            ]);
            
            \Log::info("Shift created successfully", ['shift_id' => $shift->id]);
            
            // إضافة الكاشير الرئيسي للوردية
            $shift->users()->attach(Auth::id(), [
                'join_time' => Carbon::now(),
            ]);
            
            // إضافة الموظفين الإضافيين للوردية
            if ($request->has('users') && is_array($request->users)) {
                foreach ($request->users as $userId) {
                    if ($userId != Auth::id()) {
                        $shift->users()->attach($userId, [
                            'join_time' => Carbon::now(),
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            // Clear any cached shift status
            session()->forget('current_shift');
            
            // Check if request expects JSON (from our modal)
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'تم فتح الوردية بنجاح', 'shift_id' => $shift->id]);
            }
            
            // Standard redirect for non-AJAX requests
            return redirect()->route('shifts.show', $shift)
                ->with('success', 'تم فتح الوردية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error("Error storing shift: " . $e->getMessage(), ['exception' => $e]);

            // Return JSON error for AJAX requests
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء فتح الوردية: ' . $e->getMessage()], 500);
            }
            
            // Standard redirect with error for non-AJAX
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء فتح الوردية: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل الوردية
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\View\View
     */
    public function show(Shift $shift)
    {
        $shift->load(['mainCashier', 'users', 'withdrawals.user', 'invoices.customer', 'salesReturns']);
        
        // --- Final Corrected Sales Summary Calculation with Details ---

        // 1. Initialize a final, clean summary array
        $finalSalesSummary = [
            'cash' => ['total' => 0, 'from_single' => 0, 'from_mixed' => 0],
            'visa' => ['total' => 0, 'from_single' => 0, 'from_mixed' => 0],
            'transfer' => ['total' => 0, 'from_single' => 0, 'from_mixed' => 0],
            'credit' => ['total' => 0, 'from_single' => 0, 'from_mixed' => 0], // Credit is always single
        ];
        
        // 2. Process single-payment invoices
        $singlePaymentInvoices = $shift->invoices()
            ->whereIn('status', ['paid', 'completed'])
            ->whereIn('type', ['cash', 'visa', 'transfer', 'credit'])
            ->get();
            
        foreach ($singlePaymentInvoices as $invoice) {
            if (isset($finalSalesSummary[$invoice->type])) {
                $finalSalesSummary[$invoice->type]['from_single'] += $invoice->total;
                $finalSalesSummary[$invoice->type]['total'] += $invoice->total;
            }
        }

        // 3. Process mixed-payment invoices
        $mixedPaymentInvoices = $shift->invoices()
            ->whereIn('status', ['paid', 'completed'])
            ->whereIn('type', ['mixed', 'multiple_payment'])
            ->with('payments') // Eager load payments
            ->get();
        
        foreach ($mixedPaymentInvoices as $invoice) {
            foreach ($invoice->payments as $payment) {
                if (isset($finalSalesSummary[$payment->method])) {
                    $finalSalesSummary[$payment->method]['from_mixed'] += $payment->amount;
                    $finalSalesSummary[$payment->method]['total'] += $payment->amount;
                }
            }
        }
        
        // Create the collection for the view from the final summary
        $paymentMethodsWithDetails = collect($finalSalesSummary)->map(function ($data, $method) {
            return (object)[
                'payment_method' => $method,
                'total_amount' => $data['total'],
                'from_single' => $data['from_single'],
                'from_mixed' => $data['from_mixed'],
            ];
        })->values();

        // Calculate total sales for the shift
        $totalSales = $shift->invoices()->whereIn('status', ['paid', 'completed'])->sum('total');
        
        // Calculate total cash in drawer for the 'expected balance' display
        $totalCashInDrawer = $finalSalesSummary['cash']['total'];
        
        // Calculate sales by invoice type (cash/credit)
        $salesByInvoiceType = $shift->invoices()
            ->select('type', DB::raw('SUM(total) as total_amount'))
            ->groupBy('type')
            ->pluck('total_amount', 'type')
            ->toArray();
            
        // Calculate sales by order type (delivery/takeaway)
        $salesByOrderType = $shift->invoices()
            ->select('order_type', DB::raw('SUM(total) as total_amount'))
            ->groupBy('order_type')
            ->pluck('total_amount', 'order_type')
            ->toArray();
            
        // This part is now redundant because of the new logic above, but we keep it for reference
        // and ensure other parts of the view depending on it don't break immediately.
        $multiSalesData = DB::table('invoices')
            ->select(
                'type',
                DB::raw('COUNT(*) as invoice_count'), 
                DB::raw('SUM(total) as total_amount')
            )
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['paid', 'completed'])
            ->groupBy('type')
            ->get();
            
        // Format multi-sales data for display
        $multiSalesFormatted = [];
        foreach ($multiSalesData as $data) {
            $multiSalesFormatted[$data->type] = [
                'count' => $data->invoice_count,
                'total' => $data->total_amount
            ];
        }
        
        // The new `salesByPaymentMethod` array is now the source of truth for the payment summary.
        // We will pass it to the view and update the view to use it.
        // $paymentMethodsWithCounts = collect($salesByPaymentMethod)->map(function ($data, $method) {
        //     return (object)[
        //         'payment_method' => $method,
        //         'invoice_count' => $data['count'],
        //         'total_amount' => $data['total'],
        //     ];
        // })->values();


        // Calculate total sales for the shift
        // $totalSales = $allInvoices->sum('total'); // This line is now redundant
        
        // Calculate returns summary for sales returns and purchase returns
        $salesReturnsSummary = $shift->salesReturns()
            ->selectRaw('COUNT(*) as count, SUM(total_returned_amount) as total')
            ->first();
            
        $purchaseReturnsSummary = $shift->purchaseReturns()
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->first();
            
        $totalSalesReturns = $salesReturnsSummary ? $salesReturnsSummary->total : 0;
        $totalPurchaseReturns = $purchaseReturnsSummary ? $purchaseReturnsSummary->total : 0;
        $salesReturnsCount = $salesReturnsSummary ? $salesReturnsSummary->count : 0;
        $purchaseReturnsCount = $purchaseReturnsSummary ? $purchaseReturnsSummary->count : 0;

        // --- Fetch all transaction types --- 
        $endTime = $shift->end_time ?? now();

        $invoices = $shift->invoices()->with('customer')->get();
        $withdrawals = $shift->withdrawals()->with('user')->get();
        $expenses = $shift->expenses()->with(['user', 'category'])->get();
        $deposits = $shift->deposits()->with(['user', 'source'])->get();
        $returns = $shift->salesReturns()->with(['user', 'invoice'])->get();
        $purchaseReturns = $shift->purchaseReturns()->with(['supplier', 'purchase'])->get();

        // --- Combine and format transactions --- 
        $transactions = collect();

        foreach ($invoices as $invoice) {
            $transactions->push([
                'id' => 'inv-' . $invoice->id,
                'type' => 'invoice', // فاتورة بيع
                'type_display' => 'فاتورة بيع',
                'created_at' => $invoice->created_at,
                'amount' => $invoice->total,
                'reference' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name ?? 'عميل نقدي',
                'order_type' => $invoice->order_type ?? 'takeaway',
                'invoice_type' => $invoice->type ?? 'cash',
                'payment_method' => $invoice->type ?? 'cash',
                'details' => $invoice->notes,
                'css_class' => 'text-success' // Positive impact on balance (generally)
            ]);
        }

        foreach ($withdrawals as $withdrawal) {
            $transactions->push([
                'id' => 'with-' . $withdrawal->id,
                'type' => 'withdrawal', // سحب نقدي
                'type_display' => 'سحب نقدي',
                'created_at' => $withdrawal->created_at,
                'amount' => $withdrawal->amount,
                'reference' => '-',
                'details' => $withdrawal->reason,
                'css_class' => 'text-danger' // Negative impact on cash balance
            ]);
        }

        foreach ($expenses as $expense) {
            $transactions->push([
                'id' => 'exp-' . $expense->id,
                'type' => 'expense', // مصروف
                'type_display' => 'مصروف',
                'created_at' => $expense->created_at,
                'amount' => $expense->amount,
                'reference' => $expense->reference_number ?? '-',
                'details' => $expense->category->name ?? $expense->description,
                'css_class' => 'text-danger' // Negative impact on cash balance
            ]);
        }

        foreach ($deposits as $deposit) {
            $transactions->push([
                'id' => 'dep-' . $deposit->id,
                'type' => 'deposit', // إيداع
                'type_display' => 'إيداع',
                'created_at' => $deposit->created_at,
                'amount' => $deposit->amount,
                'reference' => $deposit->reference_number ?? '-',
                'details' => $deposit->source->name ?? $deposit->description,
                'css_class' => 'text-success' // Positive impact on cash balance
            ]);
        }
        
        foreach ($returns as $return) {
            $transactions->push([
                'id' => 'ret-' . $return->id,
                'type' => 'return', // مرتجع
                'type_display' => 'مرتجع مبيعات',
                'created_at' => $return->created_at,
                'amount' => $return->total_returned_amount,
                'reference' => $return->invoice ? $return->invoice->invoice_number : '-',
                'details' => $return->reason ?? 'مرتجع مبيعات',
                'css_class' => 'text-danger' // Negative impact on cash balance
            ]);
        }
        
        // إضافة مرتجعات المشتريات للمعاملات
        foreach ($purchaseReturns as $purchaseReturn) {
            $transactions->push([
                'id' => 'pret-' . $purchaseReturn->id,
                'type' => 'purchase_return', // مرتجع مشتريات
                'type_display' => 'مرتجع مشتريات',
                'created_at' => $purchaseReturn->created_at,
                'amount' => $purchaseReturn->total_amount,
                'reference' => $purchaseReturn->return_number,
                'supplier_name' => $purchaseReturn->supplier ? $purchaseReturn->supplier->name : '-',
                'details' => $purchaseReturn->notes ?? ('مرتجع مشتريات' . ($purchaseReturn->purchase ? ' للفاتورة رقم: ' . $purchaseReturn->purchase->invoice_number : '')),
                'css_class' => 'text-success' // Positive impact on cash balance (money returned to drawer)
            ]);
        }

        // Sort transactions by date (newest first)
        $transactions = $transactions->sortByDesc('created_at');
        
        return view('shifts.show', compact(
            'shift', 
            'paymentMethodsWithDetails', // Pass the new detailed variable
            'totalSales',
            'totalCashInDrawer',
            'salesByOrderType',
            'totalSalesReturns',
            'totalPurchaseReturns',
            'salesReturnsCount',
            'purchaseReturnsCount',
            'transactions'
        ));
    }

    /**
     * عرض نموذج تحرير الوردية
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\View\View
     */
    public function edit(Shift $shift)
    {
        // لا يمكن تحرير الوردية المغلقة
        if ($shift->is_closed) {
            return redirect()->route('shifts.show', $shift)
                ->with('warning', 'لا يمكن تحرير وردية مغلقة');
        }
        
        // جلب قائمة الموظفين لإضافتهم للوردية
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['cashier', 'admin']);
        })->get();
        
        $shiftUsers = $shift->users->pluck('id')->toArray();
        
        return view('shifts.edit', compact('shift', 'users', 'shiftUsers'));
    }

    /**
     * تحديث بيانات الوردية
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Shift $shift)
    {
        // لا يمكن تحديث الوردية المغلقة
        if ($shift->is_closed) {
            return redirect()->route('shifts.show', $shift)
                ->with('warning', 'لا يمكن تحديث وردية مغلقة');
        }
        
        $request->validate([
            'notes' => 'nullable|string',
            'users' => 'nullable|array',
        ]);
        
        try {
            DB::beginTransaction();
            
            // تحديث ملاحظات الوردية
            $shift->update([
                'notes' => $request->notes,
            ]);
            
            // تحديث قائمة المستخدمين في الوردية
            $currentUsers = $shift->users->pluck('id')->toArray();
            $newUsers = $request->users ?? [];
            
            // إضافة مستخدمين جدد
            foreach ($newUsers as $userId) {
                if (!in_array($userId, $currentUsers)) {
                    $shift->users()->attach($userId, [
                        'join_time' => Carbon::now(),
                    ]);
                }
            }
            
            // إزالة المستخدمين المحذوفين (ما عدا الكاشير الرئيسي)
            foreach ($currentUsers as $userId) {
                if ($userId != $shift->main_cashier_id && !in_array($userId, $newUsers)) {
                    $shift->users()->updateExistingPivot($userId, [
                        'leave_time' => Carbon::now(),
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->route('shifts.show', $shift)
                ->with('success', 'تم تحديث الوردية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث الوردية: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * طباعة تقرير الوردية
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\View\View
     */
    public function print(Request $request, Shift $shift)
    {
        $shift->load(['mainCashier', 'users', 'withdrawals.user', 'invoices.customer', 'salesReturns']);
        
        // Calculate sales summary by payment method
        $salesByPaymentMethod = $shift->invoices()
            ->select('type as payment_method', DB::raw('SUM(total) as total_amount'))
            ->groupBy('type')
            ->pluck('total_amount', 'payment_method')
            ->toArray();
        
        // Calculate sales by order type (delivery/takeaway)
        $salesByOrderType = $shift->invoices()
            ->select('order_type', DB::raw('SUM(total) as total_amount'))
            ->groupBy('order_type')
            ->pluck('total_amount', 'order_type')
            ->toArray();
        
        // Calculate mixed sales (invoices with multiple payment methods)
        $mixedSalesCount = DB::table('invoices')
            ->where('shift_id', $shift->id)
            ->whereIn('type', ['mixed', 'multiple_payment'])
            ->whereIn('status', ['paid', 'completed'])
            ->count();
            
        $mixedSalesTotal = DB::table('invoices')
            ->where('shift_id', $shift->id)
            ->whereIn('type', ['mixed', 'multiple_payment'])
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total');
            
        // Add mixed sales to payment methods if they exist
        if ($mixedSalesCount > 0) {
            $salesByPaymentMethod['multiple_payment'] = $mixedSalesTotal;
        }

        // Calculate multi-sales data
        $multiSalesData = DB::table('invoices')
            ->select(
                'type',
                DB::raw('COUNT(*) as invoice_count'), 
                DB::raw('SUM(total) as total_amount')
            )
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['paid', 'completed'])
            ->groupBy('type')
            ->get();
            
        // Format multi-sales data for display
        $multiSalesFormatted = [];
        foreach ($multiSalesData as $data) {
            $multiSalesFormatted[$data->type] = [
                'count' => $data->invoice_count,
                'total' => $data->total_amount
            ];
        }
        
        // Get detailed payment method breakdown with counts
        $paymentMethodsWithCounts = DB::table('invoices')
            ->select(
                'type as payment_method',
                DB::raw('COUNT(*) as invoice_count'), 
                DB::raw('SUM(total) as total_amount')
            )
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['paid', 'completed'])
            ->groupBy('type')
            ->orderBy('total_amount', 'desc')
            ->get();
        
        // Calculate total sales
        $totalSales = array_sum($salesByPaymentMethod);
        
        // Calculate total number of invoices
        $totalInvoices = $shift->invoices()->count();
        
        // Calculate returns
        $totalReturns = $shift->salesReturns()->count();
        $returnsAmount = $shift->salesReturns()->sum('total_returned_amount');
        
        // Check if we need to include product details
        $withProducts = $request->has('with_products') && $request->with_products;
        $soldProducts = collect();
        $totalProfit = 0;
        
        if ($withProducts || $shift->is_closed) {
            $soldProducts = $this->getSoldProductsInShift($shift);
            $totalProfit = $soldProducts->sum('total_profit');
        }
        
        return view('shifts.print', compact(
            'shift', 
            'salesByPaymentMethod',
            'salesByOrderType',
            'multiSalesFormatted',
            'paymentMethodsWithCounts',
            'totalSales', 
            'totalInvoices', 
            'totalReturns', 
            'returnsAmount', 
            'soldProducts', 
            'totalProfit', 
            'withProducts'
        ));
    }
    
    /**
     * الحصول على المنتجات المباعة في الوردية
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Support\Collection
     */
    private function getSoldProductsInShift(Shift $shift)
    {
        // جلب جميع عناصر الفواتير في الوردية مع المنتجات والوحدات
        $invoiceItems = \App\Models\InvoiceItem::whereHas('invoice', function ($query) use ($shift) {
                $query->where('shift_id', $shift->id);
            })
            ->with(['product', 'unit', 'invoice'])
            ->get();
        
        // تجميع المنتجات حسب المنتج والوحدة
        $soldProducts = collect();
        
        foreach ($invoiceItems as $item) {
            $key = $item->product_id . '-' . $item->unit_id;
            
            if (!$soldProducts->has($key)) {
                $soldProducts->put($key, [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'منتج غير معروف',
                    'unit_id' => $item->unit_id,
                    'unit_name' => $item->unit->name ?? 'وحدة غير معروفة',
                    'quantity' => 0,
                    'total_price' => 0,
                    'total_cost' => 0,
                    'total_profit' => 0,
                ]);
            }
            
            $product = $soldProducts->get($key);
            $product['quantity'] += $item->quantity;
            $product['total_price'] += $item->total_price;
            $product['total_cost'] += ($item->unit_cost * $item->quantity);
            $product['total_profit'] += $item->profit;
            
            $soldProducts->put($key, $product);
        }
        
        // تحويل المجموعة إلى مصفوفة وترتيبها حسب إجمالي المبيعات
        return $soldProducts->values()->sortByDesc('total_price');
    }

    /**
     * إغلاق الوردية
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\RedirectResponse
     */
    public function close(Request $request, Shift $shift)
    {
        if ($shift->is_closed) {
            return back()->with('warning', 'الوردية مغلقة بالفعل');
        }
        
        $request->validate([
            'actual_closing_balance' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string',
            'print_inventory' => 'nullable|boolean',
        ]);
        
        try {
            DB::beginTransaction();
            
            $endTime = Carbon::now();

            // --- Corrected Calculation for Expected Cash ---

            // 1. Get total from invoices paid entirely with cash
            $pureCashSales = $shift->invoices()
                ->where('created_at', '<=', $endTime)
                ->where('type', 'cash')
                ->whereIn('status', ['paid', 'completed'])
                ->sum('total');

            // 2. Get the IDs of all mixed-payment invoices in the shift
            $mixedInvoiceIds = $shift->invoices()
                ->where('created_at', '<=', $endTime)
                ->whereIn('type', ['mixed', 'multiple_payment'])
                ->whereIn('status', ['paid', 'completed'])
                ->pluck('id');

            // 3. Sum only the 'cash' payments from those mixed invoices
            $cashFromMixedSales = DB::table('invoice_payments')
                ->whereIn('invoice_id', $mixedInvoiceIds)
                ->where('method', 'cash')
                ->sum('amount');
            
            // 4. The final total cash sales is the sum of pure cash and cash from mixed payments
            $totalCashSales = $pureCashSales + $cashFromMixedSales;

            $totalWithdrawals = $shift->withdrawals()->where('created_at', '<=', $endTime)->sum('amount');
            $totalExpenses = $shift->expenses()->where('created_at', '<=', $endTime)->sum('amount'); // Calculate total expenses
            $totalDeposits = $shift->deposits()->where('created_at', '<=', $endTime)->sum('amount'); // Calculate total deposits
            $totalReturns = $shift->salesReturns()->where('created_at', '<=', $endTime)->sum('total_returned_amount'); // Calculate total returns

            // Calculate expected balance - this is now the correct expected cash in drawer
            $expectedBalance = $shift->opening_balance
                               + $totalCashSales  // Correct total cash sales
                               + $totalDeposits   // الإيداعات
                               - $totalWithdrawals // المسحوبات
                               - $totalExpenses   // المصروفات
                               - $totalReturns;   // المرتجعات

            // Calculate shortage/excess
            $actualBalance = (float) $request->actual_closing_balance;
            $shortageExcess = $actualBalance - $expectedBalance;

            // احسب إجمالي كل المبيعات للحفظ في قاعدة البيانات (نقدية + آجلة)
            // تجنب احتساب الدفع المتعدد مرتين
            $totalAllSales = $shift->invoices()
                ->where('created_at', '<=', $endTime)
                ->whereIn('status', ['paid', 'completed'])
                ->sum('total');
            
            // احسب إجمالي الأرباح
            $totalProfit = $shift->invoices()
                ->where('created_at', '<=', $endTime)
                ->whereIn('status', ['paid', 'completed'])
                ->sum('profit');

            // Update shift data
            $shift->update([
                'end_time' => $endTime,
                'closing_notes' => $request->closing_notes,
                'is_closed' => true,
                'total_sales' => $totalAllSales,  // إجمالي كل المبيعات للإحصائيات
                'total_purchases' => 0, // Set total purchases to 0
                'total_withdrawals' => $totalWithdrawals,
                'total_expenses' => $totalExpenses, // Save total expenses
                'total_deposits' => $totalDeposits, // Save total deposits
                'returns_amount' => $totalReturns, // Save total returns
                'expected_closing_balance' => $expectedBalance,  // الرصيد المتوقع في الدرج (نقدي فقط)
                'actual_closing_balance' => $actualBalance,
                'cash_shortage_excess' => $shortageExcess,
                'total_profit' => $totalProfit, // إضافة إجمالي الأرباح
            ]);

            // Optional: Mark users as left the shift
            // $shift->users()->updateExistingPivot($shift->users->pluck('id')->toArray(), ['leave_time' => $endTime]);
            
            DB::commit();
            
            // Clear any cached shift status
            session()->forget('current_shift');
            
            // توجيه المستخدم دائماً إلى صفحة تفاصيل الوردية مع رسالة مناسبة
            $successMessage = 'تم إغلاق الوردية بنجاح';
            
            if ($request->has('print_inventory') && $request->print_inventory) {
                $successMessage .= '. يمكنك الآن طباعة تقرير الوردية مع الأصناف المباعة من زر الطباعة';
            }
            
            return redirect()->route('shifts.show', $shift)
                ->with('success', $successMessage);
        } catch (\Throwable $e) { // Catch Throwable for wider error capture
            DB::rollBack();
            Log::error("Error closing shift ID {$shift->id}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'حدث خطأ فادح أثناء إغلاق الوردية: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * إنشاء سحب من الوردية
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\RedirectResponse
     */
    public function withdraw(Request $request, Shift $shift)
    {
        // لا يمكن السحب من وردية مغلقة
        if ($shift->is_closed) {
            return redirect()->route('shifts.show', $shift)
                ->with('warning', 'لا يمكن السحب من وردية مغلقة');
        }
        
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // إنشاء سحب جديد
            ShiftWithdrawal::create([
                'shift_id' => $shift->id,
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'reason' => $request->reason,
            ]);
            
            // تحديث إجمالي السحب في الوردية
            $shift->withdrawal_amount = $shift->withdrawal_amount + $request->amount;
            $shift->save();
            
            DB::commit();
            
            return redirect()->route('shifts.show', $shift)
                ->with('success', 'تم تسجيل السحب من الوردية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء السحب من الوردية: ' . $e->getMessage())
                ->withInput();
        }
    }
}
