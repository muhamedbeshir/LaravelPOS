<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
        'discount_value',
        'discount_percentage',
        'price_after_discount',
        'total_price',
        'unit_cost',
        'profit',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'price_after_discount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'profit' => 'decimal:2'
    ];

    // العلاقات
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function getUnitNameAttribute()
    {
        if ($this->unit) {
            return $this->unit->name;
        }
        
        try {
            // أولا نحاول البحث في جدول الوحدات مباشرة
            $unit = Unit::find($this->unit_id);
            
            // إذا لم يتم العثور عليها، فقد تكون معرف وحدة منتج، نحاول العثور على وحدة المنتج
            if (!$unit) {
                $productUnit = ProductUnit::find($this->unit_id);
                if ($productUnit && $productUnit->unit) {
                    return $productUnit->unit->name;
                }
            } else {
                return $unit->name;
            }
            
            return 'وحدة غير معروفة';
        } catch (\Exception $e) {
            return 'وحدة غير معروفة';
        }
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['unit_name'] = $this->unit_name;
        return $array;
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    // Methods
    public function calculateTotals()
    {
        // حساب السعر بعد الخصم
        if ($this->discount_percentage > 0) {
            $this->discount_value = $this->unit_price * ($this->discount_percentage / 100);
            $this->price_after_discount = $this->unit_price - $this->discount_value;
        } elseif ($this->discount_value > 0) {
            $this->price_after_discount = $this->unit_price - $this->discount_value;
            $this->discount_percentage = ($this->discount_value / $this->unit_price) * 100;
        } else {
            $this->price_after_discount = $this->unit_price;
        }

        // حساب الإجمالي والربح
        $this->total_price = $this->price_after_discount * $this->quantity;
        $this->profit = ($this->price_after_discount - $this->unit_cost) * $this->quantity;

        $this->save();

        // تحديث إجماليات الفاتورة
        $this->invoice->calculateTotals();
    }

    public function deductFromStock()
    {
        try {
            // Find the ProductUnit based on product_id and unit_id (where unit_id is now a generic unit ID)
            $productUnit = ProductUnit::where('product_id', $this->product_id)
                                    ->where('unit_id', $this->unit_id)
                                    ->first();
            
            if (!$productUnit) {
                throw new \Exception("ProductUnit not found for Product ID: {$this->product_id} and Unit ID: {$this->unit_id}");
            }
            
            $genericUnit = $this->unit; // This is now directly the Unit model

            if (!$genericUnit) {
                throw new \Exception("Generic unit not found for Unit ID: {$this->unit_id}");
            }

            $baseQuantity = $genericUnit->convertToBaseUnit($this->quantity);
            
            $availablePurchases = PurchaseItem::where('product_id', $this->product_id)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('production_date')
                ->get();

            $remainingQuantity = $baseQuantity;
            $totalCost = 0;

            foreach ($availablePurchases as $purchase) {
                if ($remainingQuantity <= 0) break;

                $quantityToDeduct = min($remainingQuantity, $purchase->remaining_quantity);
                $purchase->remaining_quantity -= $quantityToDeduct;
                $purchase->save();

                $totalCost += $quantityToDeduct * $purchase->purchase_price;
                $remainingQuantity -= $quantityToDeduct;

                StockMovement::recordMovement([
                    'product_id' => $this->product_id,
                    'unit_id' => $productUnit->id, // Use the ProductUnit ID for stock movements
                    'quantity' => $genericUnit->convertFromBaseUnit($quantityToDeduct),
                    'movement_type' => 'out',
                    'reference_type' => 'App\\Models\\InvoiceItem',
                    'reference_id' => $this->id,
                    'employee_id' => auth()->id() ?? 1,
                    'notes' => 'خصم من المخزون - فاتورة مبيعات #' . $this->invoice->invoice_number
                ]);
            }

            if ($remainingQuantity > 0) {
                throw new \Exception('الكمية المطلوبة غير متوفرة في المخزون');
            }

            $this->unit_cost = $baseQuantity > 0 ? $totalCost / $baseQuantity : 0;
            $this->save();
            $product = $this->product;
            $product->stock_quantity -= $baseQuantity;
            $product->save();

            ProductLog::create([
                'product_id' => $product->id,
                'event' => 'بيع في فاتورة', // Sold in invoice
                'quantity' => -$baseQuantity,
                'reference' => 'فاتورة مبيعات #' . $this->invoice->invoice_number,
            ]);

            if ($product->stock_quantity <= $product->alert_quantity) {
                // TODO: Alert
            }

        } catch (\Exception $e) {
            throw new \Exception('خطأ في خصم المخزون: ' . $e->getMessage());
        }
    }

    public function returnToStock()
    {
        try {
            // Find the ProductUnit based on product_id and unit_id (where unit_id is now a generic unit ID)
            $productUnit = ProductUnit::where('product_id', $this->product_id)
                                    ->where('unit_id', $this->unit_id)
                                    ->first();
            
            if (!$productUnit) {
                throw new \Exception("ProductUnit not found for Product ID: {$this->product_id} and Unit ID: {$this->unit_id}");
            }
            
            $genericUnit = $this->unit; // This is now directly the Unit model

            if (!$genericUnit) {
                throw new \Exception("Generic unit not found for Unit ID: {$this->unit_id}");
            }
            
            $baseQuantity = $genericUnit->convertToBaseUnit($this->quantity);

            StockMovement::recordMovement([
                'product_id' => $this->product_id,
                'unit_id' => $productUnit->id, // Use the ProductUnit ID for stock movements
                'quantity' => $this->quantity,
                'movement_type' => 'in',
                'reference_type' => 'App\\Models\\InvoiceItem',
                'reference_id' => $this->id,
                'employee_id' => auth()->id() ?? 1,
                'notes' => 'إعادة للمخزون - إلغاء فاتورة مبيعات #' . $this->invoice->invoice_number
            ]);

            $product = $this->product;
            $product->stock_quantity += $baseQuantity;
            $product->save();

        } catch (\Exception $e) {
            throw new \Exception('خطأ في إعادة المخزون: ' . $e->getMessage());
        }
    }
} 