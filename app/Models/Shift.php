<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\ShiftWithdrawal;
use App\Models\User;
use App\Models\ShiftUser;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Deposit;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_number',
        'start_time',
        'end_time',
        'opening_balance',
        'notes',
        'closing_notes',
        'is_closed',
        'main_cashier_id',
        'total_purchases',
        'total_sales',
        'total_withdrawals',
        'total_expenses',
        'total_deposits',
        'expected_closing_balance',
        'actual_closing_balance',
        'cash_shortage_excess',
        'returns_amount',
        'total_profit'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_notes' => 'string',
        'is_closed' => 'boolean',
        'total_purchases' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_withdrawals' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_deposits' => 'decimal:2',
        'expected_closing_balance' => 'decimal:2',
        'actual_closing_balance' => 'decimal:2',
        'cash_shortage_excess' => 'decimal:2',
        'returns_amount' => 'decimal:2',
        'visa_sales' => 'decimal:2',
        'total_profit' => 'decimal:2'
    ];

    /**
     * العلاقة مع الكاشير الرئيسي
     */
    public function mainCashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'main_cashier_id');
    }

    /**
     * العلاقة مع المستخدمين في الوردية
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_users')
            ->withPivot('join_time', 'leave_time')
            ->withTimestamps()
            ->using(ShiftUser::class);
    }

    /**
     * العلاقة مع عمليات السحب من الوردية
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(ShiftWithdrawal::class);
    }

    /**
     * العلاقة مع المبيعات في الوردية
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * العلاقة مع الفواتير في الوردية
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * العلاقة مع المرتجعات في الوردية
     */
    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    /**
     * العلاقة مع مرتجعات المشتريات في الوردية
     */
    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * Get purchases made during this shift.
     */
    public function purchases()
    {
        // Check if end_time is set, otherwise use current time for open shifts
        $endTime = $this->end_time ?? now();

        // Fetch purchases created within the shift's start and end times
        // Note: We sum 'paid_amount' in the controller, as total_amount might not reflect cash paid from drawer
        return Purchase::where('created_at', '>=', $this->start_time)
                       ->where('created_at', '<=', $endTime);
    }

    /**
     * Get expenses made during this shift.
     */
    public function expenses()
    {
        $endTime = $this->end_time ?? now();
        return Expense::where('created_at', '>=', $this->start_time)
                      ->where('created_at', '<=', $endTime);
    }

    /**
     * Get deposits made during this shift.
     */
    public function deposits()
    {
        $endTime = $this->end_time ?? now();
        return Deposit::where('created_at', '>=', $this->start_time)
                      ->where('created_at', '<=', $endTime);
    }

    /**
     * Accessor to get the current total purchases for the shift.
     * If the shift is closed, it returns the stored total_purchases.
     * If the shift is open, it calculates the sum dynamically.
     */
    public function getCurrentPurchasesTotalAttribute()
    {
        if ($this->is_closed && $this->total_purchases !== null) {
            // For closed shifts, return the value calculated on closing.
            return $this->total_purchases;
        } else {
            // For open shifts, calculate dynamically.
            $endTime = now(); // Use current time as the end for open shifts
            return Purchase::where('created_at', '>=', $this->start_time)
                           ->where('created_at', '<=', $endTime)
                           ->sum('paid_amount');
        }
    }

    /**
     * Accessor to get the current total expenses for the shift.
     */
    public function getCurrentExpensesTotalAttribute()
    {
        if ($this->is_closed && $this->total_expenses !== null) {
            return $this->total_expenses;
        } else {
            $endTime = now();
            return Expense::where('created_at', '>=', $this->start_time)
                          ->where('created_at', '<=', $endTime)
                          ->sum('amount');
        }
    }

    /**
     * Accessor to get the current total deposits for the shift.
     */
    public function getCurrentDepositsTotalAttribute()
    {
        if ($this->is_closed && $this->total_deposits !== null) {
            return $this->total_deposits;
        } else {
            $endTime = now();
            return Deposit::where('created_at', '>=', $this->start_time)
                          ->where('created_at', '<=', $endTime)
                          ->sum('amount');
        }
    }

    /**
     * Accessor to get the current total sales for the shift.
     */
    public function getCurrentSalesTotalAttribute()
    {
        if ($this->is_closed && $this->total_sales !== null) {
            return $this->total_sales;
        } else {
            return $this->invoices()->sum('total');
        }
    }

    /**
     * Accessor to get the current total returns for the shift.
     */
    public function getCurrentReturnsTotalAttribute()
    {
        if ($this->is_closed && $this->returns_amount !== null) {
            return $this->returns_amount;
        } else {
            $endTime = now();
            return SalesReturn::where('shift_id', $this->id)
                             ->where('created_at', '>=', $this->start_time)
                             ->where('created_at', '<=', $endTime)
                             ->sum('total_returned_amount');
        }
    }
    
    /**
     * Accessor to get the current total purchase returns for the shift.
     */
    public function getCurrentPurchaseReturnsTotalAttribute()
    {
        if ($this->is_closed && $this->purchase_returns_amount !== null) {
            return $this->purchase_returns_amount;
        }
        // Use the sum of purchase returns for this shift
        $endTime = $this->end_time ?? now();
        return PurchaseReturn::where('shift_id', $this->id)
                           ->where('created_at', '>=', $this->start_time)
                           ->where('created_at', '<=', $endTime)
                           ->sum('total_amount');
    }

    /**
     * حساب الرصيد المتوقع
     */
    public function calculateExpectedBalance()
    {
        // حساب الرصيد المتوقع في الدرج (النقدي فقط)
        // استخدام المبيعات النقدية فقط وليس كل المبيعات
        $totalCashSales = $this->invoices()->where('type', 'cash')->where('status', 'paid')->sum('total');
        $totalPurchases = $this->total_purchases ?? $this->purchases()->sum('paid_amount');
        $totalWithdrawals = $this->total_withdrawals ?? $this->withdrawals()->sum('amount');
        $totalExpenses = $this->total_expenses ?? $this->expenses()->sum('amount');
        $totalDeposits = $this->total_deposits ?? $this->deposits()->sum('amount');
        $totalReturns = $this->returns_amount ?? $this->salesReturns()->sum('total_returned_amount');
        
        // إضافة مرتجعات المشتريات (تضاف للرصيد لأنها استرداد لمال تم دفعه)
        $totalPurchaseReturns = $this->purchaseReturns()->sum('total_amount');

        $this->expected_closing_balance = $this->opening_balance
                                       + $totalCashSales      // المبيعات النقدية فقط
                                       + $totalDeposits       // الإيداعات
                                       + $totalPurchaseReturns // مرتجعات المشتريات (إضافة لأنها استرداد لمال)
                                       - $totalPurchases      // المشتريات
                                       - $totalWithdrawals    // المسحوبات
                                       - $totalExpenses       // المصروفات
                                       - $totalReturns;       // المرتجعات

        return $this->expected_closing_balance;
    }

    /**
     * حساب الفرق بين الرصيد المتوقع والفعلي
     */
    public function calculateDifference()
    {
        if ($this->actual_closing_balance !== null) {
            $this->difference = $this->actual_closing_balance - $this->expected_closing_balance;
        }
        
        return $this->difference;
    }

    /**
     * جلب الوردية المفتوحة الحالية للمستخدم الحالي فقط
     * @param bool $forceFresh Whether to force a fresh query without caching
     * @return \App\Models\Shift|null
     */
    public static function getCurrentOpenShift($forceFresh = false)
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // وردية مفتوحة للمستخدم الحالي (ككاشير رئيسي أو مشارك لم يغادر بعد)
        $query = self::where('is_closed', false) // Only get shifts that are NOT closed
            ->where(function ($q) use ($user) {
                $q->where('main_cashier_id', $user->id)
                  ->orWhereHas('users', function ($q2) use ($user) {
                      $q2->where('user_id', $user->id)
                         ->whereNull('shift_users.leave_time');
                  });
            });

        // If forcing a fresh query, don't use query cache
        if ($forceFresh) {
            $query->withoutGlobalScopes();
        }

        $shift = $query->latest()->first();
        
        // Double-check that the shift is actually open
        if ($shift && $shift->is_closed) {
            return null; // Don't return closed shifts
        }
        
        return $shift;
    }
}
