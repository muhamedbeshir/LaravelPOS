<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Unit;
use App\Models\StockMovement;
use App\Models\Color;
use App\Models\Size;
use App\Models\ProductVariant;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category_id',
        'main_unit_id',
        'barcode',
        'has_serial',
        'serial_number',
        'image',
        'alert_quantity',
        'stock_quantity',
        'is_active'
    ];

    protected $casts = [
        'has_serial' => 'boolean',
        'alert_quantity' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // العلاقة مع المجموعة
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // العلاقة مع وحدات المنتج
    public function units()
    {
        return $this->hasMany(ProductUnit::class)->with('unit');
    }

    // العلاقة مع وحدات المنتج (اسم بديل لسهولة الاستخدام)
    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * العلاقة مع الألوان
     */
    public function colors()
    {
        return $this->belongsToMany(Color::class, 'product_variants')
            ->withPivot('id', 'size_id', 'sku', 'barcode', 'stock_quantity')
            ->withTimestamps();
    }

    /**
     * العلاقة مع المقاسات
     */
    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_variants')
            ->withPivot('id', 'color_id', 'sku', 'barcode', 'stock_quantity')
            ->withTimestamps();
    }

    /**
     * العلاقة مع متغيرات المنتج
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Eager load units with unit data and add unit_name accessor
    protected static function booted()
    {
        static::retrieved(function ($product) {
            if ($product->relationLoaded('units')) {
                $product->units->each(function ($productUnit) {
                    // Add unit_name attribute if unit relationship loaded
                    if ($productUnit->unit) {
                        $productUnit->unit_name = $productUnit->unit->name;
                    } else {
                        // Fallback to get unit name directly from DB
                        try {
                            $unit = Unit::find($productUnit->unit_id);
                            $productUnit->unit_name = $unit ? $unit->name : 'Unknown Unit';
                        } catch (\Exception $e) {
                            $productUnit->unit_name = 'Unknown Unit';
                        }
                    }
                });
            }
        });
    }

    // الحصول على الوحدة الرئيسية
    public function mainUnit()
    {
        return $this->belongsTo(Unit::class, 'main_unit_id');
    }

    /**
     * Get main unit name attribute
     * This is a safer way to access the main unit name
     */
    public function getMainUnitNameAttribute()
    {
        if ($this->mainUnit) {
            return $this->mainUnit->name;
        }
        
        try {
            $unit = Unit::find($this->main_unit_id);
            return $unit ? $unit->name : 'Unknown Unit';
        } catch (\Exception $e) {
            return 'Unknown Unit';
        }
    }

    // تحويل مسار الصورة إلى URL كامل
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        return url('storage/products/' . $this->image);
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
    public function convertToMainUnit($quantity, $unitId)
    {
        $unit = Unit::findOrFail($unitId);
        return $unit->convertToBaseUnit($quantity);
    }

    // الحصول على الكمية بوحدة معينة
    public function getStockInUnit($unitId)
    {
        $unit = Unit::findOrFail($unitId);
        return $unit->convertFromBaseUnit($this->stock_quantity);
    }

    // تحديث المخزون
    public function updateStock($quantity, $unitId, $operation = 'add', $data = [])
    {
        try {
            if (!$quantity || !$unitId) {
                throw new \Exception('الكمية أو معرف الوحدة غير صحيح');
            }
            
            $movementType = $operation === 'add' ? 'in' : 'out';
            
            $movementData = array_merge([
                'product_id' => $this->id,
                'unit_id' => $unitId,
                'quantity' => $quantity,
                'movement_type' => $movementType,
                'reference_type' => 'manual_adjustment',
                'reference_id' => 0,
                'employee_id' => auth()->id(),
                'notes' => 'تعديل يدوي للمخزون'
            ], $data);

            // تسجيل حركة المخزون
            StockMovement::recordMovement($movementData);

            // إعادة تحميل المنتج للحصول على آخر تحديث للمخزون
            return $this->fresh()->stock_quantity;
        } catch (\Exception $e) {
            \Log::error('Error updating stock: ' . $e->getMessage(), [
                'product_id' => $this->id,
                'unit_id' => $unitId,
                'quantity' => $quantity,
                'operation' => $operation,
                'data' => $data
            ]);
            throw new \Exception('خطأ في تحديث المخزون: ' . $e->getMessage());
        }
    }

    // التحقق من توفر الكمية
    public function hasEnoughStock($quantity, $unitId)
    {
        $unit = Unit::findOrFail($unitId);
        $requestedQuantity = $unit->convertToBaseUnit($quantity);
        return $this->stock_quantity >= $requestedQuantity;
    }

    // العلاقة مع حركات المخزون
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // الحصول على الكمية المتوفرة بوحدة معينة
    public function getStockQuantity($unitId)
    {
        $unit = Unit::findOrFail($unitId);
        return $unit->convertFromBaseUnit($this->stock_quantity);
    }

    // الحصول على متوسط التكلفة للوحدة
    public function getAverageCost($unitId)
    {
        $purchases = $this->purchaseItems()
            ->where('unit_id', $unitId)
            ->where('remaining_quantity', '>', 0)
            ->get();
            
        if ($purchases->isEmpty()) {
            return 0;
        }
        
        $totalCost = 0;
        $totalQuantity = 0;
        
        foreach ($purchases as $purchase) {
            $totalCost += $purchase->unit_cost * $purchase->remaining_quantity;
            $totalQuantity += $purchase->remaining_quantity;
        }
        
        return $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
    }
}
