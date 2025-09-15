<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'shift_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'discount_amount',
        'payment_method',
        'payment_status',
        'notes',
    ];

    /**
     * Get the customer associated with the sale
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who created the sale
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift associated with the sale
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the items in the sale
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
} 