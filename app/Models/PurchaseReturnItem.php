<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'unit_id',
        'quantity',
        'purchase_price',
        'reason'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2'
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Calculate subtotal for this item
     */
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->purchase_price;
    }
} 