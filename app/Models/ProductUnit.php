<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PriceType;

class ProductUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_factor',
        'is_main_unit',
        'is_active',
        'cost'
    ];

    protected $casts = [
        'is_main_unit' => 'boolean',
        'is_active' => 'boolean',
        'conversion_factor' => 'decimal:2',
        'cost' => 'decimal:2'
    ];

    // العلاقة مع المنتج
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // العلاقة مع الوحدة
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    
    // العلاقة مع أسعار الوحدة
    public function prices()
    {
        return $this->hasMany(ProductUnitPrice::class);
    }

    public function barcodes()
    {
        return $this->hasMany(ProductUnitBarcode::class);
    }

    /**
     * Accessor for main_price - returns price from price_types if available
     */
    public function getMainPriceAttribute($value)
    {
        // أولاً نحاول الحصول على السعر من نظام الأسعار الجديد
        $mainPriceType = PriceType::where('code', 'main_price')->first();
        if ($mainPriceType) {
            $price = $this->prices()
                ->where('price_type_id', $mainPriceType->id)
                ->value('value');
            if ($price !== null) {
                return $price;
            }
        }
        
        // If not found in new system, return from direct column
        return $value;
    }

    /**
     * Accessor for app_price - returns price from price_types if available
     */
    public function getAppPriceAttribute($value)
    {
        // أولاً نحاول الحصول على السعر من نظام الأسعار الجديد
        $appPriceType = PriceType::where(function($query) {
            $query->where('code', 'price_2')
                  ->orWhere('code', 'app_price');
        })->first();
        
        if ($appPriceType) {
            $price = $this->prices()
                ->where('price_type_id', $appPriceType->id)
                ->value('value');
            if ($price !== null) {
                return $price;
            }
        }
        
        // If not found in new system, return from direct column
        return $value;
    }

    /**
     * Accessor for other_price - returns price from price_types if available
     */
    public function getOtherPriceAttribute($value)
    {
        // أولاً نحاول الحصول على السعر من نظام الأسعار الجديد
        $otherPriceType = PriceType::where(function($query) {
            $query->where('code', 'price_3')
                  ->orWhere('code', 'other_price');
        })->first();
        
        if ($otherPriceType) {
            $price = $this->prices()
                ->where('price_type_id', $otherPriceType->id)
                ->value('value');
            if ($price !== null) {
                return $price;
            }
        }
        
        // If not found in new system, return from direct column
        return $value;
    }

    /**
     * Get price by price type code
     */
    public function getPriceByTypeCode($priceTypeCode)
    {
        $priceType = PriceType::where('code', $priceTypeCode)->first();
        if ($priceType) {
            return $this->getPriceByType($priceType->id);
        }
        
        // Backward compatibility
        if ($priceTypeCode === 'main_price') {
            return $this->main_price;
        } elseif ($priceTypeCode === 'app_price' || $priceTypeCode === 'price_2') {
            return $this->app_price;
        } elseif ($priceTypeCode === 'other_price' || $priceTypeCode === 'price_3') {
            return $this->other_price;
        }
        
        return null;
    }

    // Add a unit_name accessor to avoid template errors
    public function getUnitNameAttribute()
    {
        // First try to get it through the relationship
        if ($this->unit) {
            return $this->unit->name;
        }
        
        // If that fails, try to fetch it directly from the database
        try {
            $unit = Unit::find($this->unit_id);
            return $unit ? $unit->name : 'Unknown Unit';
        } catch (\Exception $e) {
            return 'Unknown Unit';
        }
    }
    
    // Override toArray to include unit_name
    public function toArray()
    {
        $array = parent::toArray();
        $array['unit_name'] = $this->unit_name;
        
        // Include prices in the array
        $array['prices'] = $this->prices()->with('priceType')->get()->map(function($price) {
            return [
                'id' => $price->id,
                'price_type_id' => $price->price_type_id,
                'price_type_name' => $price->priceType->name,
                'price_type_code' => $price->priceType->code,
                'value' => $price->value,
                'is_default' => $price->priceType->is_default
            ];
        });
        
        return $array;
    }

    // العلاقة مع تاريخ الأسعار
    public function priceHistory()
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    // حفظ تاريخ تغير السعر - للتوافق مع الكود القديم
    public function savePriceHistory($priceType, $oldPrice, $newPrice)
    {
        if ($oldPrice == $newPrice) {
            return;
        }

        $changePercentage = ProductPriceHistory::calculateChangePercentage($oldPrice, $newPrice);
        $changeType = ProductPriceHistory::determineChangeType($oldPrice, $newPrice);

        return $this->priceHistory()->create([
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'change_percentage' => $changePercentage,
            'price_type' => $priceType,
            'change_type' => $changeType
        ]);
    }

    // الحصول على سعر معين حسب نوع السعر
    public function getPriceByType($priceTypeId)
    {
        $price = $this->prices()->where('price_type_id', $priceTypeId)->first();
        return $price ? $price->value : null;
    }

    // الحصول على السعر الرئيسي
    public function getMainPrice()
    {
        // أولاً نحاول الحصول على السعر من نظام الأسعار الجديد
        $mainPriceType = PriceType::where('code', 'main_price')->first();
        if ($mainPriceType) {
            $price = $this->getPriceByType($mainPriceType->id);
            if ($price !== null) {
                return $price;
            }
        }
        
        // للتوافق مع الإصدارات القديمة
        return $this->main_price;
    }

    // توليد باركود تلقائي
    public static function generateBarcode()
    {
        do {
            $barcode = mt_rand(1000000000, 9999999999);
        } while (self::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // تحويل الكمية إلى الوحدة الرئيسية
    public function convertToMainUnit($quantity)
    {
        if ($this->is_main_unit) {
            return $quantity;
        }
        return $quantity * $this->conversion_factor;
    }

    // تحويل الكمية من الوحدة الرئيسية
    public function convertFromMainUnit($quantity)
    {
        if ($this->is_main_unit) {
            return $quantity;
        }
        
        // التأكد من أن معامل التحويل ليس صفراً
        if ($this->conversion_factor <= 0) {
            return 0; // إرجاع صفر إذا كان معامل التحويل صفر أو سالب
        }
        
        return $quantity / $this->conversion_factor;
    }

    // حساب الكمية المتاحة بهذه الوحدة
    public function getAvailableStock()
    {
        if ($this->is_main_unit) {
            return $this->product->stock_quantity;
        }
        return $this->convertFromMainUnit($this->product->stock_quantity);
    }
}
