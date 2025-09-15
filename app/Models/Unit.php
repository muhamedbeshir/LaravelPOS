<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'is_base_unit',
        'parent_unit_id',
        'conversion_factor',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'conversion_factor' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // العلاقات
    public function products()
    {
        return $this->hasMany(Product::class, 'main_unit_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // العلاقة مع وحدات المنتجات
    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class);
    }

    // العلاقة مع الوحدة الأم
    public function parentUnit()
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id');
    }

    // العلاقة مع الوحدات الفرعية
    public function childUnits()
    {
        return $this->hasMany(Unit::class, 'parent_unit_id');
    }

    // تحويل الكمية إلى الوحدة الأساسية
    public function convertToBaseUnit($quantity)
    {
        if ($this->is_base_unit) {
            return $quantity;
        }

        return $quantity * $this->conversion_factor;
    }

    // تحويل الكمية من الوحدة الأساسية
    public function convertFromBaseUnit($quantity)
    {
        if ($this->is_base_unit) {
            return $quantity;
        }

        return $quantity / $this->conversion_factor;
    }

    // حساب معامل التحويل الإجمالي للوحدة
    public function getTotalConversionFactorAttribute()
    {
        if ($this->is_base_unit) {
            return 1;
        }

        $factor = $this->conversion_factor;
        $parent = $this->parentUnit;

        // نتأكد من أن الوحدة الأم موجودة وليست هي الوحدة الأساسية
        while ($parent && !$parent->is_base_unit) {
            $factor *= $parent->conversion_factor;
            $parent = $parent->parentUnit;
        }

        return $factor;
    }

    // نص التحويل الكامل
    public function getConversionTextAttribute()
    {
        if ($this->is_base_unit) {
            return '-';
        }

        // نحصل على الوحدة الأساسية
        $baseUnit = $this->getBaseUnit();
        if (!$baseUnit) {
            return $this->getDirectConversionText();
        }

        // نحسب معامل التحويل الكلي
        $totalFactor = $this->total_conversion_factor;

        return sprintf(
            '1 %s = %s %s',
            $this->name,
            number_format($totalFactor, 2),
            $baseUnit->name
        );
    }

    // نص التحويل المباشر
    public function getDirectConversionText()
    {
        if ($this->is_base_unit || !$this->parentUnit) {
            return '-';
        }

        return sprintf(
            '1 %s = %s %s',
            $this->name,
            number_format($this->conversion_factor, 2),
            $this->parentUnit->name
        );
    }

    // الحصول على الوحدة الأساسية
    public function getBaseUnit()
    {
        if ($this->is_base_unit) {
            return $this;
        }

        $unit = $this;
        while ($unit->parentUnit && !$unit->parentUnit->is_base_unit) {
            $unit = $unit->parentUnit;
        }
        return $unit->parentUnit ?? $unit;
    }

    // سلسلة التحويل الكاملة
    public function getFullConversionChain()
    {
        if ($this->is_base_unit) {
            return [$this->name];
        }

        $chain = [$this->name];
        $unit = $this;
        $conversions = [];

        while ($unit->parentUnit) {
            $parent = $unit->parentUnit;
            $chain[] = $parent->name;
            $conversions[] = sprintf(
                '1 %s = %s %s',
                $unit->name,
                number_format($unit->conversion_factor, 2),
                $parent->name
            );
            $unit = $parent;
        }

        $baseUnit = $this->getBaseUnit();
        if ($baseUnit && $this->total_conversion_factor > 1) {
            $conversions[] = sprintf(
                'إجمالي التحويل: 1 %s = %s %s',
                $this->name,
                number_format($this->total_conversion_factor, 2),
                $baseUnit->name
            );
        }

        return [
            'chain' => $chain,
            'conversions' => $conversions
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculates the total number of fundamental base units (e.g., "pieces")
     * contained within this unit.
     *
     * Assumes:
     *  - A unit with `is_base_unit = true` is the fundamental base unit (contains 1 of itself).
     *  - `conversion_factor` on a unit `X` whose `parentUnit` is `P` means:
     *    "1 unit of X contains `conversion_factor` units of P".
     *    Example: Box (parent=Piece, conversion_factor=12) => 1 Box = 12 Pieces.
     *    Example: Crate (parent=Box, conversion_factor=4) => 1 Crate = 4 Boxes.
     *
     * @return float Returns 0.0 if a cycle is detected or in case of error to prevent division by zero.
     */
    public function getPiecesInUnit(array &$visited = []): float
    {
        if (isset($visited[$this->id])) {
            // Cycle detected, log error and return 0 to prevent infinite loop and division by zero.
            // Log::error("Cycle detected in unit hierarchy while calculating pieces for unit ID: {$this->id}");
            return 0.0;
        }
        $visited[$this->id] = true;

        if ($this->is_base_unit) {
            unset($visited[$this->id]); // Clear for future calls if this object is reused
            return 1.0;
        }

        if (!$this->parentUnit) {
            // This unit is not a base unit but has no parent.
            // This could mean its conversion_factor is directly to the base piece count,
            // or it's an orphaned unit.
            // If orphaned and conversion_factor is not set, it's problematic.
            // Log::warning("Unit ID: {$this->id} ('{$this->name}') is not a base unit and has no parent. Assuming its conversion_factor ('{$this->conversion_factor}') is absolute to base pieces.");
            unset($visited[$this->id]);
            return (float)($this->conversion_factor ?: 0.0); // Return 0 if factor is null/0 to be safe
        }

        // Recursive call: My Pieces = (My factor to my Parent) * (Parent's Pieces)
        $pieces = (float)($this->conversion_factor ?: 0.0) * $this->parentUnit->getPiecesInUnit($visited);
        
        unset($visited[$this->id]);
        return $pieces;
    }
}
