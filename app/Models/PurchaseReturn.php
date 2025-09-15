<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'supplier_id',
        'employee_id',
        'shift_id',
        'return_number',
        'total_amount',
        'return_date',
        'return_type',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date:Y-m-d',
        'total_amount' => 'decimal:2'
    ];

    protected $dates = [
        'return_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function setReturnDateAttribute($value)
    {
        $this->attributes['return_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function getReturnDateAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Get the purchase that this return belongs to.
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the supplier for this return.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the employee who processed this return.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the shift during which this return was made.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /**
     * Generate a unique return number
     */
    public static function generateReturnNumber()
    {
        $lastReturn = self::latest()->first();
        $lastNumber = $lastReturn ? intval(substr($lastReturn->return_number, 4)) : 0;
        return 'PRET' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Update supplier balance after return
     */
    public function updateSupplierBalance()
    {
        if (!$this->supplier_id) {
            return;
        }
        
        // إنشاء فاتورة مورد بقيمة سالبة (مدين) للمرتجع
        $supplierInvoice = SupplierInvoice::create([
            'supplier_id' => $this->supplier_id,
            'invoice_number' => $this->return_number,
            'amount' => -$this->total_amount, // قيمة سالبة للمرتجع
            'due_date' => $this->return_date ?? now(),
            'status' => 'paid',
            'paid_amount' => -$this->total_amount,
            'remaining_amount' => 0,
            'notes' => 'مرتجع مشتريات رقم: ' . $this->return_number . ($this->notes ? ' - ' . $this->notes : '')
        ]);
        
        // تحديث إجماليات المورد
        if ($this->supplier) {
            $this->supplier->updateAmounts();
        }
        
        return $supplierInvoice;
    }
} 