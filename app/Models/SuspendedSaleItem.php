<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspendedSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'suspended_sale_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
        'discount_value',
        'discount_percentage',
        'sub_total',
        'cost_price',
        'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function suspendedSale(): BelongsTo
    {
        return $this->belongsTo(SuspendedSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
