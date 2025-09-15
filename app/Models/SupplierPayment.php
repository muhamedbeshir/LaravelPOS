<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'amount',
        'payment_method',
        'payment_type',
        'payment_date',
        'reference_number',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    // العلاقات
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function allocatedInvoices()
    {
        return $this->belongsToMany(SupplierInvoice::class, 'supplier_payment_allocations')
                    ->withPivot('amount')
                    ->withTimestamps();
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
    
    public function getPaymentTypeText()
    {
        $types = [
            'cash' => 'دفع نقدي',
            'bank' => 'دفع بنكي',
            'cheque' => 'دفع بشيك',
            'return' => 'مرتجع مشتريات'
        ];

        return $types[$this->payment_type] ?? $this->payment_type;
    }
} 