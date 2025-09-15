<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Models\SupplierInvoice;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'employee_id',
        'purchase_date',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes'
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'purchase_date' => 'date:Y-m-d',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2'
    ];

    protected $dates = [
        'date',
        'purchase_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected static function boot()
    {
        parent::boot();

        // تحديث حالة الفاتورة قبل الحفظ
        static::saving(function ($purchase) {
            // تحديث المبلغ المتبقي
            $purchase->remaining_amount = $purchase->total_amount - $purchase->paid_amount;
            
            // تحديث الحالة بناءً على المبلغ المدفوع والمتبقي
            if ($purchase->remaining_amount <= 0) {
                $purchase->status = 'paid';
            } elseif ($purchase->paid_amount > 0) {
                $purchase->status = 'partially_paid';
            } else {
                $purchase->status = 'pending';
            }
        });

        // عند إنشاء فاتورة مشتريات جديدة
        static::created(function ($purchase) {
            // إنشاء فاتورة مورد مرتبطة إذا كان هناك مورد
            if ($purchase->supplier_id) {
                // تحويل قيمة status من جدول purchases إلى القيم المسموح بها في جدول supplier_invoices
                $status = self::mapPurchaseStatusToInvoiceStatus($purchase->status, $purchase->remaining_amount);

                SupplierInvoice::create([
                    'supplier_id' => $purchase->supplier_id,
                    'invoice_number' => $purchase->invoice_number,
                    'amount' => $purchase->total_amount,
                    'due_date' => $purchase->purchase_date ? $purchase->purchase_date->addDays(30) : now()->addDays(30),
                    'status' => $status,
                    'paid_amount' => $purchase->paid_amount,
                    'remaining_amount' => $purchase->remaining_amount,
                    'notes' => "فاتورة مشتريات رقم: {$purchase->invoice_number}"
                ]);

                // تحديث إجمالي المورد
                $purchase->supplier->updateAmounts();
            }
        });

        // عند تحديث فاتورة مشتريات
        static::updated(function ($purchase) {
            // تحديث فاتورة المورد المرتبطة إذا وجدت
            if ($purchase->supplier_id) {
                // تحويل قيمة status من جدول purchases إلى القيم المسموح بها في جدول supplier_invoices
                $status = self::mapPurchaseStatusToInvoiceStatus($purchase->status, $purchase->remaining_amount);

                $supplierInvoice = SupplierInvoice::where('invoice_number', $purchase->invoice_number)
                    ->where('supplier_id', $purchase->supplier_id)
                    ->first();

                if ($supplierInvoice) {
                    $supplierInvoice->update([
                        'amount' => $purchase->total_amount,
                        'paid_amount' => $purchase->paid_amount,
                        'remaining_amount' => $purchase->remaining_amount,
                        'status' => $status
                    ]);
                } else {
                    // إذا لم توجد فاتورة مورد، قم بإنشائها
                    SupplierInvoice::create([
                        'supplier_id' => $purchase->supplier_id,
                        'invoice_number' => $purchase->invoice_number,
                        'amount' => $purchase->total_amount,
                        'due_date' => $purchase->purchase_date ? $purchase->purchase_date->addDays(30) : now()->addDays(30),
                        'status' => $status,
                        'paid_amount' => $purchase->paid_amount,
                        'remaining_amount' => $purchase->remaining_amount,
                        'notes' => "فاتورة مشتريات رقم: {$purchase->invoice_number}"
                    ]);
                }

                // تحديث إجمالي المورد
                $purchase->supplier->updateAmounts();
            }
        });
    }

    /**
     * تحويل قيمة status من جدول purchases إلى القيم المسموح بها في جدول supplier_invoices
     */
    private static function mapPurchaseStatusToInvoiceStatus($purchaseStatus, $remainingAmount)
    {
        if ($remainingAmount <= 0) {
            return 'paid';
        } elseif ($purchaseStatus == 'partially_paid') {
            return 'partially_paid';
        } else {
            return 'pending';
        }
    }

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function getDateAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function setPurchaseDateAttribute($value)
    {
        $this->attributes['purchase_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function getPurchaseDateAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            // If it can't be parsed as a date, return as is
            return $value;
        }
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public static function generateInvoiceNumber()
    {
        $lastPurchase = self::latest()->first();
        $lastNumber = $lastPurchase ? intval(substr($lastPurchase->invoice_number, 3)) : 0;
        return 'PUR' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getMorphClass()
    {
        return self::class;
    }

    /**
     * تحديث حالة الفاتورة بناءً على المبلغ المدفوع والمبلغ المتبقي
     */
    public function updateStatus()
    {
        // تحديث المبلغ المتبقي أولاً
        $this->remaining_amount = $this->total_amount - $this->paid_amount;
        
        if ($this->remaining_amount <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }
} 