<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\SalaryPayment;
use App\Models\Setting;
use App\Services\SalaryExpenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    /**
     * عرض التقرير المالي الشامل
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // التاريخ الافتراضي: الشهر الحالي
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // تحويل التواريخ إلى كائنات Carbon
        $startDateCarbon = Carbon::parse($startDate)->startOfDay();
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();

        // 1. إجمالي المبيعات
        $salesData = $this->getSalesData($startDateCarbon, $endDateCarbon);

        // 2. إجمالي المصروفات
        $expensesData = $this->getExpensesData($startDateCarbon, $endDateCarbon);

        // 3. إجمالي الرواتب
        $salariesData = $this->getSalariesData($startDateCarbon, $endDateCarbon);

        // 4. إجمالي المشتريات
        $purchasesData = $this->getPurchasesData($startDateCarbon, $endDateCarbon);

        // 5. حساب الأرباح
        $profitData = $this->calculateProfitData($salesData, $expensesData, $salariesData);

        // 6. الحصول على فئات المصروفات لعرضها في الرسم البياني
        $expenseCategories = $this->getExpenseCategoriesData($startDateCarbon, $endDateCarbon);

        // 7. الحصول على بيانات المبيعات اليومية للرسم البياني
        $dailySalesData = $this->getDailySalesData($startDateCarbon, $endDateCarbon);

        return view('reports.financial-summary', compact(
            'startDate',
            'endDate',
            'salesData',
            'expensesData',
            'salariesData',
            'purchasesData',
            'profitData',
            'expenseCategories',
            'dailySalesData'
        ));
    }

    /**
     * الحصول على بيانات المبيعات
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getSalesData(Carbon $startDate, Carbon $endDate): array
    {
        $salesQuery = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed');

        $totalSales = $salesQuery->sum('total');
        $totalSalesCount = $salesQuery->count();
        $totalSalesProfit = $salesQuery->sum('profit');

        return [
            'total' => $totalSales,
            'count' => $totalSalesCount,
            'profit' => $totalSalesProfit,
            'average' => $totalSalesCount > 0 ? $totalSales / $totalSalesCount : 0
        ];
    }

    /**
     * الحصول على بيانات المصروفات
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getExpensesData(Carbon $startDate, Carbon $endDate): array
    {
        $expensesQuery = Expense::whereBetween('created_at', [$startDate, $endDate]);

        $totalExpenses = $expensesQuery->sum('amount');
        $totalExpensesCount = $expensesQuery->count();

        return [
            'total' => $totalExpenses,
            'count' => $totalExpensesCount,
            'average' => $totalExpensesCount > 0 ? $totalExpenses / $totalExpensesCount : 0
        ];
    }

    /**
     * الحصول على بيانات الرواتب
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getSalariesData(Carbon $startDate, Carbon $endDate): array
    {
        // التحقق مما إذا كان يجب حساب الرواتب كمصروفات
        $countSalariesAsExpenses = (bool) Setting::get('count_salaries_as_expenses', true);

        $salariesQuery = SalaryPayment::whereBetween('payment_date', [$startDate, $endDate]);

        $totalSalaries = $salariesQuery->sum('amount');
        $totalSalariesCount = $salariesQuery->count();

        return [
            'total' => $totalSalaries,
            'count' => $totalSalariesCount,
            'average' => $totalSalariesCount > 0 ? $totalSalaries / $totalSalariesCount : 0,
            'count_as_expenses' => $countSalariesAsExpenses
        ];
    }

    /**
     * الحصول على بيانات المشتريات
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getPurchasesData(Carbon $startDate, Carbon $endDate): array
    {
        $purchasesQuery = Purchase::whereBetween('created_at', [$startDate, $endDate]);

        $totalPurchases = $purchasesQuery->sum('total_amount');
        $totalPurchasesCount = $purchasesQuery->count();

        return [
            'total' => $totalPurchases,
            'count' => $totalPurchasesCount,
            'average' => $totalPurchasesCount > 0 ? $totalPurchases / $totalPurchasesCount : 0
        ];
    }

    /**
     * حساب بيانات الأرباح
     *
     * @param array $salesData
     * @param array $expensesData
     * @param array $salariesData
     * @return array
     */
    private function calculateProfitData(array $salesData, array $expensesData, array $salariesData): array
    {
        $grossProfit = $salesData['profit'];
        
        $totalExpenses = $expensesData['total'];
        
        // إضافة الرواتب للمصروفات إذا كان الإعداد مفعلاً
        $salaryExpenses = $salariesData['count_as_expenses'] ? $salariesData['total'] : 0;
        
        $totalExpensesWithSalaries = $totalExpenses + $salaryExpenses;
        
        $netProfit = $grossProfit - $totalExpensesWithSalaries;
        
        $profitMargin = $salesData['total'] > 0 ? ($netProfit / $salesData['total']) * 100 : 0;

        return [
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'salary_expenses' => $salaryExpenses,
            'total_expenses_with_salaries' => $totalExpensesWithSalaries,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin
        ];
    }

    /**
     * الحصول على بيانات فئات المصروفات للرسم البياني
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getExpenseCategoriesData(Carbon $startDate, Carbon $endDate): array
    {
        $expensesByCategory = Expense::join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereBetween('expenses.created_at', [$startDate, $endDate])
            ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $expensesByCategory->pluck('name')->toArray(),
            'data' => $expensesByCategory->pluck('total')->toArray(),
        ];
    }

    /**
     * الحصول على بيانات المبيعات اليومية للرسم البياني
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getDailySalesData(Carbon $startDate, Carbon $endDate): array
    {
        $dailySales = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total_sales'),
                DB::raw('SUM(profit) as total_profit')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'dates' => $dailySales->pluck('date')->toArray(),
            'sales' => $dailySales->pluck('total_sales')->toArray(),
            'profits' => $dailySales->pluck('total_profit')->toArray(),
        ];
    }
}
