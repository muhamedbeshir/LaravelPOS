<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnitBarcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_unit_id',
        'barcode',
    ];

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
