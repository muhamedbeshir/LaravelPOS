<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'amount',
        'due_date',
        'status',
        'paid_amount',
        'remaining_amount',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date'
    ];

    // العلاقات
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments()
    {
        return $this->belongsToMany(SupplierPayment::class, 'supplier_payment_allocations')
                    ->withPivot('amount')
                    ->withTimestamps();
    }

    // الوظائف المساعدة
    public function updateStatus()
    {
        if ($this->remaining_amount <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }

    public function addPayment($amount)
    {
        $this->paid_amount += $amount;
        $this->remaining_amount = $this->amount - $this->paid_amount;
        $this->updateStatus();
    }

    public function isDue()
    {
        return $this->due_date <= now() && $this->status !== 'paid';
    }
    
    /**
     * الحصول على النص العربي لحالة الفاتورة
     */
    public function getStatusText()
    {
        switch ($this->status) {
            case 'paid':
                return 'مدفوعة بالكامل';
            case 'partially_paid':
                return 'مدفوعة جزئياً';
            case 'pending':
                return 'قيد الانتظار';
            default:
                return $this->status;
        }
    }
    
    /**
     * الحصول على فئة CSS لحالة الفاتورة
     */
    public function getStatusClass()
    {
        switch ($this->status) {
            case 'paid':
                return 'bg-success';
            case 'partially_paid':
                return 'bg-warning text-dark';
            case 'pending':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }
} 