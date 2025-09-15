<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * تعيين القيم الافتراضية للنموذج
     *
     * @var array
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * العلاقة مع المنتجات
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_variants')
            ->withPivot('id', 'color_id', 'sku', 'barcode', 'stock_quantity')
            ->withTimestamps();
    }

    /**
     * العلاقة مع متغيرات المنتجات
     */
    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }
} 