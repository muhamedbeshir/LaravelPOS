<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'title',
        'message',
        'type',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    // العلاقات
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // الوظائف المساعدة
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();
    }

    public function getTypeClass()
    {
        $classes = [
            'payment_due' => 'danger',
            'payment_received' => 'success',
            'invoice_added' => 'info',
            'warning' => 'warning'
        ];

        return $classes[$this->type] ?? 'primary';
    }
} 