<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'promotion_type',
        'applies_to', // Added this
        'discount_value',
        'minimum_purchase',
        'maximum_discount',
        'usage_limit',
        'used_count',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the products associated with this promotion.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotion_products');
    }

    /**
     * Get the customers associated with this promotion.
     */
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'promotion_customers');
    }

    /**
     * Get the coupons associated with this promotion.
     */
    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Get the invoice discount records for this promotion.
     */
    public function invoiceDiscounts()
    {
        return $this->hasMany(InvoiceDiscount::class);
    }
} 