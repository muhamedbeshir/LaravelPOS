<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class RecalculateShiftsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:recalculate {--force : Force recalculation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إعادة حساب الأرصدة المتوقعة والعجز/الزيادة للورديات المغلقة';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('هل تريد إعادة حساب جميع الورديات المغلقة؟ هذا سيحديث البيانات الموجودة.')) {
                $this->info('تم إلغاء العملية.');
                return 0;
            }
        }

        $closedShifts = Shift::where('is_closed', true)->get();
        
        if ($closedShifts->isEmpty()) {
            $this->info('لا توجد ورديات مغلقة لإعادة حسابها.');
            return 0;
        }

        $this->info("جاري إعادة حساب {$closedShifts->count()} وردية...");
        
        $progressBar = $this->output->createProgressBar($closedShifts->count());
        $progressBar->start();

        $updated = 0;
        $errors = 0;

        foreach ($closedShifts as $shift) {
            try {
                DB::beginTransaction();
                
                $endTime = $shift->end_time;

                // حساب المبالغ الصحيحة
                $totalCashSales = $shift->invoices()
                    ->where('created_at', '<=', $endTime)
                    ->where('type', 'cash')
                    ->whereIn('status', ['paid', 'completed'])
                    ->sum('total');

                $totalPurchases = $shift->purchases()
                    ->where('created_at', '<=', $endTime)
                    ->sum('paid_amount');

                $totalWithdrawals = $shift->withdrawals()
                    ->where('created_at', '<=', $endTime)
                    ->sum('amount');

                $totalExpenses = $shift->expenses()
                    ->where('created_at', '<=', $endTime)
                    ->sum('amount');

                $totalDeposits = $shift->deposits()
                    ->where('created_at', '<=', $endTime)
                    ->sum('amount');

                $totalReturns = $shift->salesReturns()
                    ->where('created_at', '<=', $endTime)
                    ->sum('total_returned_amount');

                // حساب الرصيد المتوقع الصحيح
                $correctExpectedBalance = $shift->opening_balance
                                        + $totalCashSales
                                        + $totalDeposits
                                        - $totalPurchases
                                        - $totalWithdrawals
                                        - $totalExpenses
                                        - $totalReturns;

                // حساب العجز/الزيادة الصحيح
                $correctShortageExcess = $shift->actual_closing_balance - $correctExpectedBalance;

                // تحديث البيانات
                $shift->update([
                    'expected_closing_balance' => $correctExpectedBalance,
                    'cash_shortage_excess' => $correctShortageExcess,
                    'total_purchases' => $totalPurchases,
                    'total_withdrawals' => $totalWithdrawals,
                    'total_expenses' => $totalExpenses,
                    'total_deposits' => $totalDeposits,
                    'returns_amount' => $totalReturns,
                ]);

                DB::commit();
                $updated++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("خطأ في إعادة حساب الوردية {$shift->shift_number}: " . $e->getMessage());
                $errors++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        
        $this->info("تم الانتهاء من إعادة الحساب:");
        $this->info("- تم تحديث: {$updated} وردية");
        if ($errors > 0) {
            $this->warn("- أخطاء: {$errors} وردية");
        }

        return 0;
    }
} 