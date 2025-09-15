<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_default',
        'is_active'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // العلاقة مع أسعار وحدات المنتجات
    public function productUnitPrices()
    {
        return $this->hasMany(ProductUnitPrice::class);
    }

    // البحث عن الأنواع النشطة فقط
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // الحصول على نوع السعر الافتراضي
    public static function getDefault()
    {
        return self::where('is_default', true)->first() ?? self::first();
    }

    // التحقق مما إذا كان هذا هو نوع السعر الافتراضي
    public function isDefault(): bool
    {
        return $this->is_default;
    }
}
