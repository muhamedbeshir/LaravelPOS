<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuspendedSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'customer_id',
        'user_id',
        'invoice_type',
        'order_type',
        'price_type_code',
        'discount_value',
        'discount_percentage',
        'total_amount',
        'paid_amount',
        'notes',
        'delivery_employee_id',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delivery_employee_id');
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class, 'price_type_code', 'code');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SuspendedSaleItem::class);
    }
}
