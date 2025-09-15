<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'employee_id',
        'status_id',
        'shipping_company_id',
        'shipping_status_id',
        'shipping_cost',
        'tracking_number',
        'shipped_at',
        'estimated_delivery_date',
        'amount',
        'collected_amount',
        'remaining_amount',
        'is_paid',
        'is_returned',
        'delivery_date',
        'dispatched_at',
        'payment_date',
        'return_date',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'is_paid' => 'boolean',
        'is_returned' => 'boolean',
        'delivery_date' => 'datetime',
        'dispatched_at' => 'datetime',
        'shipped_at' => 'datetime',
        'estimated_delivery_date' => 'datetime',
        'payment_date' => 'datetime',
        'return_date' => 'datetime'
    ];

    // العلاقات
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function status()
    {
        return $this->belongsTo(DeliveryStatus::class, 'status_id');
    }

    /**
     * العلاقة مع شركة الشحن
     */
    public function shippingCompany()
    {
        return $this->belongsTo(ShippingCompany::class);
    }

    /**
     * العلاقة مع حالة الشحن
     */
    public function shippingStatus()
    {
        return $this->belongsTo(ShippingStatus::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // الدوال المساعدة
    public function updateStatus($statusCode, $userId = null)
    {
        $status = DeliveryStatus::where('code', $statusCode)->first();
        
        if (!$status) {
            throw new \Exception('حالة التوصيل غير موجودة');
        }
        
        $this->status_id = $status->id;
        
        if ($statusCode !== 'dispatched') {
            // Only nullify dispatched_at if we are moving *away* from dispatched to a non-final state
            // or if we are setting a final state (delivered_pending_payment, paid, returned)
            // This logic might need refinement based on exact flow.
            // For now, we explicitly set it if status IS 'dispatched'.
        }
        if ($statusCode !== 'delivered_pending_payment' && $statusCode !== 'paid' && $statusCode !== 'returned') {
            // $this->delivery_date = null; // Consider if this should be nulled unless explicitly set
        }
        if ($statusCode !== 'paid') {
            // $this->payment_date = null;
            // $this->is_paid = false;
        }
        if ($statusCode !== 'returned') {
            // $this->return_date = null;
            // $this->is_returned = false;
        }

        if ($statusCode === 'dispatched') {
            $this->dispatched_at = now();
            $this->delivery_date = null;
        } elseif ($statusCode === 'delivered_pending_payment') {
            $this->delivery_date = now();
            
            // تحديث وقت التوصيل في الفاتورة أيضاً
            $this->invoice->delivery_time = now();
            $this->invoice->save();
        } elseif ($statusCode === 'paid') {
            $this->is_paid = true;
            $this->payment_date = now();
            $this->delivery_date = $this->delivery_date ?? now();
            $this->collected_amount = $this->amount;
            $this->remaining_amount = 0;
        } elseif ($statusCode === 'returned') {
            $this->is_returned = true;
            $this->return_date = now();
            $this->delivery_date = $this->delivery_date ?? now();
        }
        
        if ($userId) {
            $this->updated_by = $userId;
        }
        
        $this->save();
        
        // تحديث حالة الفاتورة أيضاً
        if ($statusCode === 'paid') {
            $this->invoice->payment_status = 'paid';
            $this->invoice->paid_amount = $this->invoice->total;
            $this->invoice->remaining_amount = 0;
            $this->invoice->save();
        } elseif ($statusCode === 'returned') {
            $this->invoice->status = 'cancelled';
            $this->invoice->save();
            
            // إعادة المنتجات للمخزن
            foreach ($this->invoice->items as $item) {
                $item->returnToStock();
            }
        }
        
        return true;
    }
    
    public function addPayment($amount, $userId = null)
    {
        if ($amount <= 0) {
            throw new \Exception('المبلغ يجب أن يكون أكبر من صفر');
        }
        
        if ($amount > $this->remaining_amount) {
            throw new \Exception('المبلغ أكبر من المبلغ المتبقي');
        }
        
        $this->collected_amount += $amount;
        $this->remaining_amount = $this->amount - $this->collected_amount;
        
        if ($this->remaining_amount <= 0) {
            $this->is_paid = true;
            $this->payment_date = now();
            $this->updateStatus('paid', $userId);
        }
        
        if ($userId) {
            $this->updated_by = $userId;
        }
        
        $this->save();
        
        // تحديث الفاتورة أيضاً
        $this->invoice->paid_amount += $amount;
        $this->invoice->remaining_amount = $this->invoice->total - $this->invoice->paid_amount;
        
        if ($this->invoice->remaining_amount <= 0) {
            $this->invoice->payment_status = 'paid';
        } elseif ($this->invoice->paid_amount > 0) {
            $this->invoice->payment_status = 'partially_paid';
        }
        
        $this->invoice->save();
        
        return true;
    }

    /**
     * تحديث حالة الشحن
     */
    public function updateShippingStatus($statusCode, $userId = null)
    {
        $status = ShippingStatus::where('code', $statusCode)->first();
        
        if (!$status) {
            throw new \Exception('حالة الشحن غير موجودة');
        }
        
        $this->shipping_status_id = $status->id;
        
        // تحديث الحقول المرتبطة بحالة الشحن
        if ($statusCode === 'shipped') {
            $this->shipped_at = now();
        } elseif ($statusCode === 'delivered') {
            // تحديث حالة التوصيل أيضاً عند تسليم الشحنة
            $this->updateStatus('delivered_pending_payment', $userId);
        } elseif ($statusCode === 'paid') {
            // تحديث حالة التوصيل أيضاً عند الدفع
            $this->updateStatus('paid', $userId);
        } elseif ($statusCode === 'returned') {
            // تحديث حالة التوصيل أيضاً عند الإرجاع
            $this->updateStatus('returned', $userId);
        }
        
        if ($userId) {
            $this->updated_by = $userId;
        }
        
        $this->save();
        
        return true;
    }
} 