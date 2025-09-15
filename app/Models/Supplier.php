<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // العلاقات
    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function notifications()
    {
        return $this->hasMany(SupplierNotification::class);
    }

    // الوظائف المساعدة
    public function updateAmounts()
    {
        // حساب إجماليات المورد من فواتير المشتريات وفواتير المرتجعات
        $purchases = \App\Models\Purchase::where('supplier_id', $this->id);
        $totalAmount = $purchases->sum('total_amount');
        $paidAmount = $purchases->sum('paid_amount');
        
        // حساب إجمالي فواتير المرتجعات (القيم السالبة)
        $returnInvoices = $this->invoices()->where('amount', '<', 0)->sum('amount');
        
        // إضافة قيمة المرتجعات (القيم السالبة) إلى إجمالي المدفوعات
        $totalAmount += $returnInvoices;
        
        $remainingAmount = $totalAmount - $paidAmount;
        
        $this->total_amount = $totalAmount;
        $this->paid_amount = $paidAmount;
        $this->remaining_amount = $remainingAmount;
        $this->save();
        
        // تحديث فواتير الموردين لتتطابق مع فواتير المشتريات
        $purchases = \App\Models\Purchase::where('supplier_id', $this->id)->get();
        foreach ($purchases as $purchase) {
            $supplierInvoice = $this->invoices()->where('invoice_number', $purchase->invoice_number)
                ->where('amount', '>', 0) // فقط الفواتير العادية (غير المرتجعات)
                ->first();
            
            if ($supplierInvoice) {
                // تحديث فاتورة المورد الموجودة
                $status = $purchase->remaining_amount <= 0 ? 'paid' : ($purchase->paid_amount > 0 ? 'partially_paid' : 'pending');
                $supplierInvoice->update([
                    'amount' => $purchase->total_amount,
                    'paid_amount' => $purchase->paid_amount,
                    'remaining_amount' => $purchase->remaining_amount,
                    'status' => $status
                ]);
            }
        }
    }

    public function hasUnpaidInvoices()
    {
        return $this->remaining_amount > 0;
    }

    public function getStatusClass()
    {
        return $this->remaining_amount > 0 ? 'text-danger' : 'text-success';
    }

    public function getStatusText()
    {
        return $this->remaining_amount > 0 ? 'عليه فلوس ' : 'تم السداد';
    }

    public function getDueInvoices()
    {
        return $this->invoices()
            ->whereIn('status', ['pending', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getUpcomingInvoices()
    {
        return $this->invoices()
            ->whereIn('status', ['pending', 'partially_paid'])
            ->where('due_date', '>', now())
            ->orderBy('due_date')
            ->get();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
} 