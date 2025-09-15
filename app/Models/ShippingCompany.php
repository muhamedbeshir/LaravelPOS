<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingCompany extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'default_cost',
        'is_active',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'default_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة مع معاملات التوصيل
     */
    public function deliveryTransactions()
    {
        return $this->hasMany(DeliveryTransaction::class);
    }

    /**
     * نطاق للشركات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * حساب عدد الشحنات المكتملة
     */
    public function getCompletedDeliveriesCountAttribute()
    {
        return $this->deliveryTransactions()
            ->whereHas('status', function($query) {
                $query->where('name', 'تم التسليم');
            })
            ->count();
    }

    /**
     * حساب عدد الشحنات قيد التنفيذ
     */
    public function getPendingDeliveriesCountAttribute()
    {
        return $this->deliveryTransactions()
            ->whereHas('status', function($query) {
                $query->whereNotIn('name', ['تم التسليم', 'ملغي']);
            })
            ->count();
    }

    /**
     * حساب إجمالي تكاليف الشحن
     */
    public function getTotalShippingCostAttribute()
    {
        return $this->deliveryTransactions()->sum('shipping_cost');
    }
}
