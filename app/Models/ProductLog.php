<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'event',
        'quantity',
        'reference',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 