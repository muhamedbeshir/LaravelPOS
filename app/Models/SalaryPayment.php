<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    // العلاقات
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * علاقة مع السلف التي تم خصمها من هذا الراتب
     */
    public function deductedAdvances()
    {
        return $this->hasMany(EmployeeAdvance::class);
    }

    // الوظائف المساعدة
    public function getPaymentMethodText()
    {
        $methods = [
            'cash' => 'نقداً',
            'bank_transfer' => 'تحويل بنكي',
            'check' => 'شيك'
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }
} 