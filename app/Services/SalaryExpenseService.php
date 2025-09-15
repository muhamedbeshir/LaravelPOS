<?php

namespace App\Services;

use App\Models\SalaryPayment;
use App\Models\Setting;
use Carbon\Carbon;

class SalaryExpenseService
{
    /**
     * حساب إجمالي مصروفات الرواتب خلال فترة زمنية محددة
     *
     * @param string $startDate تاريخ البداية (Y-m-d)
     * @param string $endDate تاريخ النهاية (Y-m-d)
     * @return float إجمالي مصروفات الرواتب
     */
    public static function calculateSalaryExpenses(string $startDate, string $endDate): float
    {
        // التحقق مما إذا كان يجب حساب الرواتب كمصروفات
        $countSalariesAsExpenses = (bool) Setting::get('count_salaries_as_expenses', true);
        
        // إذا كان الإعداد معطلًا، نعيد صفر
        if (!$countSalariesAsExpenses) {
            return 0;
        }
        
        // حساب إجمالي الرواتب المدفوعة خلال الفترة المحددة
        $totalSalaries = SalaryPayment::whereBetween('payment_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->sum('amount');
            
        return (float) $totalSalaries;
    }
    
    /**
     * خصم مصروفات الرواتب من إجمالي الأرباح
     *
     * @param float $totalProfit إجمالي الأرباح
     * @param string $startDate تاريخ البداية (Y-m-d)
     * @param string $endDate تاريخ النهاية (Y-m-d)
     * @return float صافي الأرباح بعد خصم الرواتب
     */
    public static function deductSalariesFromProfit(float $totalProfit, string $startDate, string $endDate): float
    {
        $salaryExpenses = self::calculateSalaryExpenses($startDate, $endDate);
        return $totalProfit - $salaryExpenses;
    }
    
    /**
     * الحصول على إجمالي مصروفات الرواتب وصافي الأرباح
     *
     * @param float $totalProfit إجمالي الأرباح
     * @param string $startDate تاريخ البداية (Y-m-d)
     * @param string $endDate تاريخ النهاية (Y-m-d)
     * @return array مصفوفة تحتوي على إجمالي مصروفات الرواتب وصافي الأرباح
     */
    public static function getSalaryExpensesAndNetProfit(float $totalProfit, string $startDate, string $endDate): array
    {
        $salaryExpenses = self::calculateSalaryExpenses($startDate, $endDate);
        $netProfit = $totalProfit - $salaryExpenses;
        
        return [
            'salary_expenses' => $salaryExpenses,
            'net_profit' => $netProfit
        ];
    }
} 