<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'is_negative_allowed'
    ];

    protected $casts = [
        'minimum_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'is_negative_allowed' => 'boolean'
    ];

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

    public function validateStock($currentStock, $requestedQuantity)
    {
        $newStock = $currentStock - $requestedQuantity;

        if (!$this->is_negative_allowed && $newStock < 0) {
            throw new \Exception("لا يمكن إتمام العملية. الكمية المتوفرة: {$currentStock}");
        }

        if ($this->maximum_stock && $newStock > $this->maximum_stock) {
            throw new \Exception("الكمية تتجاوز الحد الأقصى المسموح به");
        }

        if ($newStock <= $this->reorder_point) {
            // إرسال إشعار بإعادة الطلب
            event(new \App\Events\LowStockAlert($this->product, $newStock));
        }

        return true;
    }
} 