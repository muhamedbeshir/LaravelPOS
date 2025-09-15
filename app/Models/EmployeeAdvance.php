<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdvance extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'amount',
        'date',
        'repayment_date',
        'is_deducted_from_salary',
        'deducted_amount',
        'salary_payment_id',
        'notes',
        'status',
        'created_by',
    ];

    /**
     * الحقول التي يجب تحويلها
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'deducted_amount' => 'decimal:2',
        'date' => 'date',
        'repayment_date' => 'date',
        'is_deducted_from_salary' => 'boolean',
    ];

    /**
     * علاقة مع الموظف
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * علاقة مع دفعة الراتب
     *
     * @return BelongsTo
     */
    public function salaryPayment(): BelongsTo
    {
        return $this->belongsTo(SalaryPayment::class);
    }

    /**
     * علاقة مع المستخدم الذي أنشأ السلفة
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * حساب المبلغ المتبقي من السلفة
     *
     * @return float
     */
    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->amount - (float) $this->deducted_amount;
    }

    /**
     * تحديث حالة السلفة بناءً على المبلغ المخصوم
     *
     * @return void
     */
    public function updateStatus(): void
    {
        $remainingAmount = $this->remaining_amount;

        if ($remainingAmount <= 0) {
            $this->status = 'paid';
        } elseif ($this->deducted_amount > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }
}
