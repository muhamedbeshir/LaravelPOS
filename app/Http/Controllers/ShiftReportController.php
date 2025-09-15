<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\ShiftWithdrawal;
use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftReportController extends Controller
{
    /**
     * عرض تقرير تفصيلي للوردية
     *
     * @param Shift $shift
     * @return \Illuminate\View\View
     */
    public function show(Shift $shift)
    {
        // تحميل البيانات المرتبطة
        $shift->load(['mainCashier', 'users', 'withdrawals.user', 'sales', 'invoices']);
        
        // إحصائيات المبيعات حسب طريقة الدفع (من جدول Sales)
        $salesByPaymentMethod = $shift->sales()
            ->select('payment_method', DB::raw('SUM(total_amount) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('total_amount', 'payment_method')
            ->toArray();
        
        $salesCountByPaymentMethod = $shift->sales()
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('count', 'payment_method')
            ->toArray();
            
        // إحصائيات المبيعات حسب طريقة الدفع (من جدول Invoices)
        $invoicesByPaymentMethod = $shift->invoices()
            ->select('type as payment_method', DB::raw('SUM(total) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('total_amount', 'payment_method')
            ->toArray();
            
        $invoicesCountByPaymentMethod = $shift->invoices()
            ->select('type as payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'payment_method')
            ->toArray();
            
        // دمج البيانات من المبيعات والفواتير
        $methods = ['cash', 'credit', 'visa', 'transfer'];
        $totalByPaymentMethod = array_fill_keys($methods, 0);
        $countByPaymentMethod = array_fill_keys($methods, 0);

        // 1) مبيعات جدول Sales (تأتي كمبلغ إجمالي لكل صف)
        foreach ($salesByPaymentMethod as $method => $amount) {
            if (isset($totalByPaymentMethod[$method])) {
                $totalByPaymentMethod[$method] += $amount;
                $countByPaymentMethod[$method] += $salesCountByPaymentMethod[$method] ?? 0;
            }
        }

        // 2) فواتير جدول Invoices
        foreach ($shift->invoices as $invoice) {
            if ($invoice->type === 'mixed') {
                // وزّع مبالغ الدفعات على الطرق المختلفة
                foreach ($invoice->payments as $pay) {
                    $m = $pay->method;
                    if (!isset($totalByPaymentMethod[$m])) continue;
                    $totalByPaymentMethod[$m] += $pay->amount;
                    $countByPaymentMethod[$m] += 1; // نعدّ كل دفعة كعملية
                }
            } else {
                $m = $invoice->type;
                if (!isset($totalByPaymentMethod[$m])) continue;
                $totalByPaymentMethod[$m] += $invoice->total;
                $countByPaymentMethod[$m] += 1;
            }
        }
        
        // حساب المرتجعات من جدول Sale (المبيعات السالبة)
        $negativeReturns = Sale::where('shift_id', $shift->id)
            ->where('total_amount', '<', 0)
            ->get();
            
        $negativeReturnsTotal = abs($negativeReturns->sum('total_amount'));
        $negativeReturnsCount = $negativeReturns->count();
        
        // حساب المرتجعات من جدول SalesReturn
        $salesReturns = SalesReturn::where('shift_id', $shift->id)->get();
        $salesReturnsTotal = $salesReturns->sum('total_returned_amount');
        $salesReturnsCount = $salesReturns->count();
        
        // إجمالي المرتجعات
        $totalReturns = $negativeReturnsTotal + $salesReturnsTotal;
        $returnsCount = $negativeReturnsCount + $salesReturnsCount;
        
        // حساب عدد العمليات
        $totalSalesCount = $shift->sales()->count();
        $totalInvoicesCount = $shift->invoices()->count();
        $totalTransactions = $totalSalesCount + $totalInvoicesCount + $returnsCount;
        
        // حساب المسحوبات
        $withdrawals = $shift->withdrawals;
        $totalWithdrawals = $withdrawals->sum('amount');
        $withdrawalsCount = $withdrawals->count();
        
        // تفاصيل الفرق النقدي
        $difference = $shift->difference;
        $differenceStatus = $difference > 0 ? 'زيادة' : ($difference < 0 ? 'نقص' : 'متطابق');
        
        // الإحصائيات المالية
        $financialStats = [
            'total_sales'   => array_sum($totalByPaymentMethod),
            'cash_sales'    => $totalByPaymentMethod['cash'] ?? 0,
            'credit_sales'  => $totalByPaymentMethod['credit'] ?? 0,
            'visa_sales'    => $totalByPaymentMethod['visa'] ?? 0,
            'transfer_sales'=> $totalByPaymentMethod['transfer'] ?? 0,
            'total_returns' => $totalReturns,
            'total_withdrawals' => $totalWithdrawals,
            'opening_balance' => $shift->opening_balance,
            'expected_closing_balance' => $shift->expected_closing_balance,
            'actual_closing_balance' => $shift->actual_closing_balance,
            'difference' => $difference,
            'difference_status' => $differenceStatus,
        ];
        
        // الإحصائيات العددية
        $countStats = [
            'total_transactions' => $totalTransactions,
            'sales_count' => $totalSalesCount,
            'invoices_count' => $totalInvoicesCount,
            'returns_count' => $returnsCount,
            'withdrawals_count' => $withdrawalsCount,
            'cash_count' => $countByPaymentMethod['cash'] ?? 0,
            'credit_count' => $countByPaymentMethod['credit'] ?? 0,
            'visa_count' => $countByPaymentMethod['visa'] ?? 0,
            'transfer_count' => $countByPaymentMethod['transfer'] ?? 0,
        ];
        
        // آخر 10 عمليات مبيعات/فواتير
        $latestTransactions = $this->getLatestTransactions($shift, 10);
        
        return view('reports.shift_report', compact(
            'shift', 
            'financialStats', 
            'countStats', 
            'totalByPaymentMethod',
            'countByPaymentMethod',
            'withdrawals',
            'latestTransactions'
        ));
    }
    
    /**
     * عرض نموذج البحث عن تقارير الورديات
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // جلب آخر 10 ورديات
        $shifts = Shift::with('mainCashier')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        return view('reports.shifts_index', compact('shifts'));
    }
    
    /**
     * البحث عن تقارير الورديات حسب التاريخ
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $query = Shift::with('mainCashier')->orderBy('created_at', 'desc');
        
        if ($startDate) {
            $query->whereDate('start_time', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('start_time', '<=', $endDate);
        }
        
        $shifts = $query->get();
        
        return view('reports.shifts_index', compact('shifts', 'startDate', 'endDate'));
    }
    
    /**
     * طباعة تقرير الوردية
     * 
     * @param Shift $shift
     * @return \Illuminate\View\View
     */
    public function print(Shift $shift)
    {
        // استخدام نفس المنطق الموجود في دالة show
        $shift->load(['mainCashier', 'users', 'withdrawals.user', 'sales', 'invoices']);
        
        // إحصائيات المبيعات حسب طريقة الدفع (من جدول Sales)
        $salesByPaymentMethod = $shift->sales()
            ->select('payment_method', DB::raw('SUM(total_amount) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('total_amount', 'payment_method')
            ->toArray();
        
        $salesCountByPaymentMethod = $shift->sales()
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('count', 'payment_method')
            ->toArray();
            
        // إحصائيات المبيعات حسب طريقة الدفع (من جدول Invoices)
        $invoicesByPaymentMethod = $shift->invoices()
            ->select('type as payment_method', DB::raw('SUM(total) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('total_amount', 'payment_method')
            ->toArray();
            
        $invoicesCountByPaymentMethod = $shift->invoices()
            ->select('type as payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'payment_method')
            ->toArray();
            
        // دمج البيانات من المبيعات والفواتير
        $methods = ['cash', 'credit', 'visa', 'transfer'];
        $totalByPaymentMethod = array_fill_keys($methods, 0);
        $countByPaymentMethod = array_fill_keys($methods, 0);

        // 1) مبيعات جدول Sales (تأتي كمبلغ إجمالي لكل صف)
        foreach ($salesByPaymentMethod as $method => $amount) {
            if (isset($totalByPaymentMethod[$method])) {
                $totalByPaymentMethod[$method] += $amount;
                $countByPaymentMethod[$method] += $salesCountByPaymentMethod[$method] ?? 0;
            }
        }

        // 2) فواتير جدول Invoices
        foreach ($shift->invoices as $invoice) {
            if ($invoice->type === 'mixed') {
                // وزّع مبالغ الدفعات على الطرق المختلفة
                foreach ($invoice->payments as $pay) {
                    $m = $pay->method;
                    if (!isset($totalByPaymentMethod[$m])) continue;
                    $totalByPaymentMethod[$m] += $pay->amount;
                    $countByPaymentMethod[$m] += 1; // نعدّ كل دفعة كعملية
                }
            } else {
                $m = $invoice->type;
                if (!isset($totalByPaymentMethod[$m])) continue;
                $totalByPaymentMethod[$m] += $invoice->total;
                $countByPaymentMethod[$m] += 1;
            }
        }
        
        // حساب المرتجعات من جدول Sale (المبيعات السالبة)
        $negativeReturns = Sale::where('shift_id', $shift->id)
            ->where('total_amount', '<', 0)
            ->get();
            
        $negativeReturnsTotal = abs($negativeReturns->sum('total_amount'));
        $negativeReturnsCount = $negativeReturns->count();
        
        // حساب المرتجعات من جدول SalesReturn
        $salesReturns = SalesReturn::where('shift_id', $shift->id)->get();
        $salesReturnsTotal = $salesReturns->sum('total_returned_amount');
        $salesReturnsCount = $salesReturns->count();
        
        // إجمالي المرتجعات
        $totalReturns = $negativeReturnsTotal + $salesReturnsTotal;
        $returnsCount = $negativeReturnsCount + $salesReturnsCount;
        
        // حساب عدد العمليات
        $totalSalesCount = $shift->sales()->count();
        $totalInvoicesCount = $shift->invoices()->count();
        $totalTransactions = $totalSalesCount + $totalInvoicesCount + $returnsCount;
        
        // حساب المسحوبات
        $withdrawals = $shift->withdrawals;
        $totalWithdrawals = $withdrawals->sum('amount');
        $withdrawalsCount = $withdrawals->count();
        
        // تفاصيل الفرق النقدي
        $difference = $shift->difference;
        $differenceStatus = $difference > 0 ? 'زيادة' : ($difference < 0 ? 'نقص' : 'متطابق');
        
        // الإحصائيات المالية
        $financialStats = [
            'total_sales'      => array_sum($totalByPaymentMethod),
            'cash_sales'       => $totalByPaymentMethod['cash'] ?? 0,
            'credit_sales'     => $totalByPaymentMethod['credit'] ?? 0,
            'visa_sales'       => $totalByPaymentMethod['visa'] ?? 0,
            'transfer_sales'   => $totalByPaymentMethod['transfer'] ?? 0,
            'total_returns'    => $totalReturns,
            'total_withdrawals'=> $totalWithdrawals,
            'opening_balance'  => $shift->opening_balance,
            'expected_closing_balance' => $shift->expected_closing_balance,
            'actual_closing_balance'   => $shift->actual_closing_balance,
            'difference'       => $difference,
            'difference_status'=> $differenceStatus,
        ];
        
        // الإحصائيات العددية
        $countStats = [
            'total_transactions' => $totalTransactions,
            'sales_count'       => $totalSalesCount,
            'invoices_count'    => $totalInvoicesCount,
            'returns_count'     => $returnsCount,
            'withdrawals_count' => $withdrawalsCount,
            'cash_count'        => $countByPaymentMethod['cash'] ?? 0,
            'credit_count'      => $countByPaymentMethod['credit'] ?? 0,
            'visa_count'        => $countByPaymentMethod['visa'] ?? 0,
            'transfer_count'    => $countByPaymentMethod['transfer'] ?? 0,
        ];
        
        // جلب جميع العمليات للطباعة
        $allTransactions = $this->getLatestTransactions($shift, 1000);
        
        return view('reports.shift_report_print', compact(
            'shift', 
            'financialStats', 
            'countStats', 
            'totalByPaymentMethod',
            'countByPaymentMethod',
            'withdrawals',
            'allTransactions'
        ));
    }
    
    /**
     * جلب آخر العمليات (مبيعات وفواتير) في الوردية
     * 
     * @param Shift $shift
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function getLatestTransactions(Shift $shift, $limit = 10)
    {
        $combinedTransactions = collect();
        
        // جلب المبيعات
        $sales = $shift->sales()->with('user')->latest()->get();
        foreach($sales as $sale) {
            $combinedTransactions->push([
                'id' => $sale->id,
                'type' => 'sale',
                'reference' => $sale->invoice_number,
                'created_at' => $sale->created_at,
                'amount' => $sale->total_amount,
                'payment_method' => $sale->payment_method,
                'name' => $sale->user->name ?? 'غير محدد',
                'model' => $sale
            ]);
        }
        
        // جلب الفواتير
        $invoices = $shift->invoices()->with('customer')->latest()->get();
        foreach($invoices as $invoice) {
            $combinedTransactions->push([
                'id' => $invoice->id,
                'type' => 'invoice',
                'reference' => $invoice->invoice_number,
                'created_at' => $invoice->created_at,
                'amount' => $invoice->total,
                'payment_method' => $invoice->type,
                'name' => $invoice->customer->name ?? 'غير محدد',
                'model' => $invoice
            ]);
        }
        
        // جلب المرتجعات
        $returns = SalesReturn::where('shift_id', $shift->id)
            ->with(['user', 'invoice'])
            ->latest()
            ->get();
        
        foreach($returns as $return) {
            $reference = $return->invoice_id ? 
                ('فاتورة #' . ($return->invoice->reference_no ?? $return->invoice_id)) : 
                ('مرتجع #' . $return->id);
                
            $combinedTransactions->push([
                'id' => $return->id,
                'type' => 'return',
                'reference' => $reference,
                'created_at' => $return->created_at,
                'amount' => -1 * $return->total_returned_amount, // Use negative amount for returns
                'payment_method' => 'return',
                'name' => $return->user->name ?? 'غير محدد',
                'model' => $return
            ]);
        }
        
        // ترتيب العمليات حسب التاريخ وأخذ آخر العمليات
        return $combinedTransactions->sortByDesc('created_at')->take($limit);
    }
} 