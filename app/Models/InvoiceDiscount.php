<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'promotion_id',
        'coupon_id',
        'discount_amount',
    ];

    /**
     * Get the invoice that this discount was applied to.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the promotion for this discount.
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Get the coupon used for this discount, if any.
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
