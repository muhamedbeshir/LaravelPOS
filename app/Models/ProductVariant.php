<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة الجماعية
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 
        'color_id', 
        'size_id', 
        'name',
        'sku', 
        'barcode', 
        'stock_quantity'
    ];

    /**
     * العلاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * العلاقة مع اللون
     */
    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * العلاقة مع المقاس
     */
    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * الحصول على الاسم الكامل للمتغير
     */
    public function getFullNameAttribute()
    {
        if ($this->name) {
            return $this->name;
        }
        
        $name = $this->product->name;
        
        if ($this->color) {
            $name .= ' - ' . $this->color->name;
        }
        
        if ($this->size) {
            $name .= ' - ' . $this->size->name;
        }
        
        return $name;
    }
} 