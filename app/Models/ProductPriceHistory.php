<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPriceHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_price_history';

    protected $fillable = [
        'product_unit_id',
        'old_price',
        'new_price',
        'change_percentage',
        'price_type',
        'change_type',
        'notes'
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'change_percentage' => 'decimal:2'
    ];

    // العلاقة مع وحدة المنتج
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }

    // حساب نسبة التغير في السعر
    public static function calculateChangePercentage($oldPrice, $newPrice)
    {
        if ($oldPrice == 0) return 100;
        return (($newPrice - $oldPrice) / $oldPrice) * 100;
    }

    // تحديد نوع التغير (زيادة أو نقصان)
    public static function determineChangeType($oldPrice, $newPrice)
    {
        if ($newPrice > $oldPrice) {
            return 'increase';
        } elseif ($newPrice < $oldPrice) {
            return 'decrease';
        }
        return 'no_change';
    }
} 