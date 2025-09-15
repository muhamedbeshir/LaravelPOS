<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingHistory extends Model
{
    use HasFactory;

    /**
     * اسم الجدول المرتبط بالنموذج
     */
    protected $table = 'shipping_history';

    /**
     * الحقول التي يمكن تعبئتها بشكل جماعي
     */
    protected $fillable = [
        'delivery_transaction_id',
        'shipping_status_id',
        'notes',
        'user_id'
    ];

    /**
     * العلاقة مع معاملة التوصيل
     */
    public function deliveryTransaction()
    {
        return $this->belongsTo(DeliveryTransaction::class);
    }

    /**
     * العلاقة مع حالة الشحن
     */
    public function shippingStatus()
    {
        return $this->belongsTo(ShippingStatus::class);
    }

    /**
     * العلاقة مع المستخدم الذي قام بالتحديث
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
