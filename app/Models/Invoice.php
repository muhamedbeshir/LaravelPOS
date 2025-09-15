<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'shift_invoice_number',
        'type',
        'order_type',
        'customer_id',
        'delivery_employee_id',
        'price_type',
        'subtotal',
        'discount_value',
        'discount_percentage',
        'total',
        'paid_amount',
        'remaining_amount',
        'profit',
        'status',
        'payment_status',
        'delivery_status',
        'delivery_time',
        'shift_id',
        'notes'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'profit' => 'decimal:2',
        'delivery_time' => 'datetime'
    ];

    // العلاقات
    public function customer()
    {
        return $this->belongsTo(Customer::class)->withDefault([
            'name' => 'عميل محذوف',
            'phone' => '-',
            'address' => '-'
        ]);
    }

    public function deliveryEmployee()
    {
        return $this->belongsTo(Employee::class, 'delivery_employee_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function deliveryTransaction()
    {
        return $this->hasOne(DeliveryTransaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id');
    }

    /**
     * Get all the discounts applied to this invoice.
     */
    public function invoiceDiscounts()
    {
        return $this->hasMany(InvoiceDiscount::class);
    }

    // Scopes
    public function scopeCash($query)
    {
        return $query->where('type', 'cash');
    }

    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDelivery($query)
    {
        return $query->where('order_type', 'delivery');
    }

    public function scopeTakeaway($query)
    {
        return $query->where('order_type', 'takeaway');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Methods
    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('total_price');
        $this->total = $this->subtotal;

        if ($this->discount_percentage > 0) {
            $this->discount_value = $this->subtotal * ($this->discount_percentage / 100);
            $this->total = $this->subtotal - $this->discount_value;
        } elseif ($this->discount_value > 0) {
            $this->total = $this->subtotal - $this->discount_value;
            $this->discount_percentage = ($this->discount_value / $this->subtotal) * 100;
        }

        $this->profit = $this->items->sum('profit');
        $this->remaining_amount = $this->total - $this->paid_amount;

        // تحديث حالة الدفع
        if ($this->remaining_amount <= 0) {
            $this->payment_status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = 'partially_paid';
        } else {
            $this->payment_status = 'unpaid';
        }

        $this->save();

        // تحديث رصيد العميل أو موظف الدليفري
        $this->updateBalances();
    }

    public function updateBalances()
    {
        try {
            if ($this->type === 'credit') {
                // إضافة المبلغ على العميل مباشرة
                // نستخدم قيمة سالبة لأن الرصيد السالب يعني مديونية على العميل
                $this->customer->addToBalance(-$this->remaining_amount);
            }
            
            // إذا كانت فاتورة دليفري، نقوم بإنشاء معاملة دليفري
            if ($this->order_type === 'delivery' && !$this->deliveryTransaction) {
                // البحث عن حالة "الطلبية جاهزة في انتظار الخروج"
                $readyStatus = DeliveryStatus::where('code', 'ready')->first();
                
                if (!$readyStatus) {
                    throw new \Exception('حالة الدليفري غير موجودة');
                }
                
                // إنشاء معاملة دليفري جديدة
                DeliveryTransaction::create([
                    'invoice_id' => $this->id,
                    'customer_id' => $this->customer_id,
                    'employee_id' => $this->delivery_employee_id,
                    'status_id' => $readyStatus->id,
                    'amount' => $this->total,
                    'collected_amount' => $this->paid_amount,
                    'remaining_amount' => $this->remaining_amount,
                    'is_paid' => $this->payment_status === 'paid',
                    'created_by' => auth()->id() ?? 1
                ]);
            }
        } catch (\Exception $e) {
            throw new \Exception('خطأ في تحديث الأرصدة: ' . $e->getMessage());
        }
    }

    public function complete()
    {
        try {
            if ($this->status === 'completed') {
                return;
            }

            // تحديث حالة الفاتورة
            $this->status = 'completed';
            
            if ($this->order_type === 'delivery') {
                $this->delivery_status = 'delivered';
                
                // تحديث وقت التوصيل
                if (!$this->delivery_time) {
                    $this->delivery_time = now();
                }
                
                // تحديث معاملة الدليفري إذا وجدت
                if ($this->deliveryTransaction) {
                    $this->deliveryTransaction->updateStatus('delivered_pending_payment', auth()->id() ?? 1);
                }
            }

            $this->save();
        } catch (\Exception $e) {
            throw new \Exception('خطأ في إكمال الفاتورة: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        try {
            if ($this->status === 'cancelled') {
                return;
            }

            // تحديث حالة الفاتورة
            $this->status = 'cancelled';
            $this->save();

            // إعادة الكميات للمخزن
            foreach ($this->items as $item) {
                $item->returnToStock();
            }

            // تصفير الأرصدة - نتعامل فقط مع رصيد العميل
            if ($this->type === 'credit') {
                // إذا كان هناك مبلغ متبقي، نقوم بتصفير مديونية العميل
                if ($this->remaining_amount > 0) {
                    $this->customer->addToBalance($this->remaining_amount);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('خطأ في إلغاء الفاتورة: ' . $e->getMessage());
        }
    }

    public function addPayment($amount, string $method = 'cash', ?string $reference = null)
    {
        try {
            // سجل الدفعة في جدول invoice_payments إن وجدت علاقة
            if (class_exists(InvoicePayment::class)) {
                $this->payments()->create([
                    'method'    => $method,
                    'amount'    => $amount,
                    'reference' => $reference,
                ]);
            }

            $this->paid_amount += $amount;
            $this->remaining_amount = $this->total - $this->paid_amount;

            if ($this->remaining_amount <= 0) {
                $this->payment_status = 'paid';
            } elseif ($this->paid_amount > 0) {
                $this->payment_status = 'partially_paid';
            }

            $this->save();

            // تحديث رصيد العميل فقط في حالة الفواتير الآجلة
            if ($this->type === 'credit') {
                // إضافة المبلغ المدفوع لتقليل مديونية العميل
                $this->customer->addToBalance($amount);
            }
            
            // تحديث معاملة الدليفري إذا وجدت
            if ($this->order_type === 'delivery' && $this->deliveryTransaction) {
                $this->deliveryTransaction->addPayment($amount, auth()->id() ?? 1);
            }
        } catch (\Exception $e) {
            throw new \Exception('خطأ في إضافة الدفعة: ' . $e->getMessage());
        }
    }

    public static function generateNumber()
    {
        $lastInvoice = self::latest()->first();
        $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 3)) : 0;
        return 'INV' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }
} 