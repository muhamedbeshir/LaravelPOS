<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockCount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'notes',
        'count_date'
    ];

    protected $casts = [
        'count_date' => 'datetime'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function items()
    {
        return $this->hasMany(StockCountItem::class);
    }
}
