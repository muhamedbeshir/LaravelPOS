<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductUnitPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_unit_id',
        'price_type_id',
        'value',
        'is_active'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // العلاقة مع وحدة المنتج
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }

    // العلاقة مع نوع السعر
    public function priceType()
    {
        return $this->belongsTo(PriceType::class);
    }

    // البحث عن الأسعار النشطة فقط
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // حفظ تاريخ تغير السعر
    public function savePriceHistory($oldValue)
    {
        if ($oldValue == $this->value) {
            return;
        }

        // الحصول على نوع السعر
        $priceType = $this->priceType;
        if (!$priceType) {
            return;
        }

        $changePercentage = ProductPriceHistory::calculateChangePercentage($oldValue, $this->value);
        $changeType = ProductPriceHistory::determineChangeType($oldValue, $this->value);

        return ProductPriceHistory::create([
            'product_unit_id' => $this->product_unit_id,
            'price_type_id' => $this->price_type_id,
            'old_price' => $oldValue,
            'new_price' => $this->value,
            'change_percentage' => $changePercentage,
            'price_type' => $priceType->code,
            'change_type' => $changeType
        ]);
    }
}
