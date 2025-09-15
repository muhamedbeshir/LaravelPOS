<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'unit_id',
        'quantity',
        'purchase_price',
        'selling_price',
        'expected_profit',
        'profit_percentage',
        'production_date',
        'expiry_date',
        'alert_days_before_expiry'
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getUnitNameAttribute()
    {
        if ($this->unit) {
            return $this->unit->name;
        }
        
        try {
            $unit = Unit::find($this->unit_id);
            return $unit ? $unit->name : 'Unknown Unit';
        } catch (\Exception $e) {
            return 'Unknown Unit';
        }
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['unit_name'] = $this->unit_name;
        return $array;
    }

    public function calculateProfit()
    {
        $this->expected_profit = ($this->selling_price - $this->purchase_price) * $this->quantity;
        $this->profit_percentage = (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
    }

    public function getExpiryDateAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            // If it can't be parsed as a date, return as is
            return $value;
        }
    }

    public function getProductionDateAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            // If it can't be parsed as a date, return as is
            return $value;
        }
    }
} 