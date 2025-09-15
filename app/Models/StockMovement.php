<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use Illuminate\Support\Facades\Log;

class StockMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'unit_id',
        'quantity',
        'before_quantity',
        'after_quantity',
        'movement_type',
        'reference_type',
        'reference_id',
        'employee_id',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'before_quantity' => 'decimal:2',
        'after_quantity' => 'decimal:2'
    ];

    // العلاقات
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // دالة مساعدة لإنشاء حركة مخزن
    public static function recordMovement(array $data)
    {
        try {
            Log::debug('StockMovement::recordMovement - Input Data:', $data);

            if (!isset($data['unit_id']) || !isset($data['product_id'])) {
                Log::error('StockMovement - Missing unit_id or product_id in input data', $data);
                throw new \Exception('معرف الوحدة أو المنتج غير موجود في البيانات');
            }
            
            $unit = Unit::find($data['unit_id']);
            if (!$unit) {
                Log::error('StockMovement - Unit not found', ['unit_id' => $data['unit_id']]);
                throw new \Exception('الوحدة غير موجودة برقم: ' . $data['unit_id']);
            }
            Log::debug('StockMovement - Unit found:', $unit->toArray());
            
            $product = Product::find($data['product_id']);
            if (!$product) {
                Log::error('StockMovement - Product not found', ['product_id' => $data['product_id']]);
                throw new \Exception('المنتج غير موجود برقم: ' . $data['product_id']);
            }
            Log::debug('StockMovement - Product BEFORE stock update:', $product->toArray());

            // Ensure quantity is treated as a float for calculations
            $quantityForConversion = isset($data['quantity']) ? (float)$data['quantity'] : 0.0;
            $baseQuantity = $unit->convertToBaseUnit($quantityForConversion);
            Log::debug('StockMovement - Calculated baseQuantity:', [
                'baseQuantity' => $baseQuantity, 
                'original_quantity' => $quantityForConversion, 
                'conversion_factor' => $unit->conversion_factor, 
                'unit_is_base' => $unit->is_base_unit
            ]);

            $currentBaseQuantity = isset($product->stock_quantity) ? (float)$product->stock_quantity : 0.0;
            Log::debug('StockMovement - currentBaseQuantity for product:', ['currentBaseQuantity' => $currentBaseQuantity]);
            
            $movementType = $data['movement_type'] ?? 'in';
            $newBaseQuantity = $currentBaseQuantity; // Initialize with current quantity

            if ($movementType === 'in') {
                $newBaseQuantity = $currentBaseQuantity + $baseQuantity;
            } else { // Assuming 'out' or other types that decrease stock
                if ($currentBaseQuantity < $baseQuantity) {
                    Log::warning('StockMovement - Insufficient stock for movement', [
                        'product_id' => $product->id,
                        'current_stock' => $currentBaseQuantity,
                        'requested_deduction' => $baseQuantity
                    ]);
                    // Depending on business rules, you might throw an exception here or allow stock to go negative.
                    // For now, let's allow it to proceed and record the negative stock if that occurs.
                    // throw new \Exception('الكمية المطلوبة غير متوفرة في المخزون');
                }
                $newBaseQuantity = $currentBaseQuantity - $baseQuantity;
            }
            Log::debug('StockMovement - newBaseQuantity calculated:', ['newBaseQuantity' => $newBaseQuantity]);

            $product->stock_quantity = $newBaseQuantity;
            $saveResult = $product->save();
            Log::debug('StockMovement - Product AFTER stock update attempt:', [
                'product_data' => $product->toArray(), 
                'save_result' => $saveResult
            ]);

            $employeeId = $data['employee_id'] ?? null;

            $movementLog = self::create([
                'product_id' => $data['product_id'],
                'unit_id' => $data['unit_id'],
                'quantity' => $quantityForConversion,
                'before_quantity' => $currentBaseQuantity,
                'after_quantity' => $newBaseQuantity,
                'movement_type' => $movementType,
                'reference_type' => $data['reference_type'] ?? 'manual',
                'reference_id' => $data['reference_id'] ?? null, // Allow null reference_id
                'employee_id' => $employeeId,
                'notes' => $data['notes'] ?? null
            ]);
            Log::debug('StockMovement - Movement log created:', $movementLog->toArray());

            return $movementLog;
        } catch (\Exception $e) {
            Log::error('StockMovement::recordMovement - Exception:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'input_data' => $data]);
            // Re-throw the exception so the calling transaction can be rolled back
            throw new \Exception('خطأ في تسجيل حركة المخزون: ' . $e->getMessage(), 0, $e);
        }
    }

    // تحويل نوع المرجع إلى اسم مختصر
    public function getReferenceTypeDisplayAttribute()
    {
        if (!$this->reference_type) {
            return 'غير معروف';
        }
        
        switch ($this->reference_type) {
            case 'App\\Models\\Purchase':
                return 'فاتورة شراء';
            case 'App\\Models\\Sale':
            case 'App\\Models\\Invoice':
                return 'فاتورة بيع';
            case 'App\\Models\\StockAdjustment':
                return 'تعديل مخزون';
            case 'App\\Models\\StockCount':
                return 'جرد مخزون';
            default:
                return 'تعديل يدوي';
        }
    }

    // Add a unit_name accessor to avoid template errors
    public function getUnitNameAttribute()
    {
        // First try to get it through the relationship
        if ($this->unit) {
            return $this->unit->name ?? 'Unknown Unit';
        }
        
        // If that fails, try to fetch it directly from the database
        try {
            if (!$this->unit_id) {
                return 'Unknown Unit';
            }
            
            $unit = Unit::find($this->unit_id);
            return $unit ? ($unit->name ?? 'Unknown Unit') : 'Unknown Unit';
        } catch (\Exception $e) {
            return 'Unknown Unit';
        }
    }
    
    // Override the toArray method to add the unit_name attribute
    public function toArray()
    {
        $array = parent::toArray();
        $array['unit_name'] = $this->unit_name;
        return $array;
    }
} 