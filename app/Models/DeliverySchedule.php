<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class DeliverySchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'delivery_zone_id',
        'employee_id',
        'scheduled_date',
        'scheduled_time_slot',
        'actual_delivery_time',
        'status',
        'delivery_notes',
        'customer_signature',
        'delivery_proof_image',
        'delivery_cost',
        'actual_distance'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_delivery_time' => 'datetime',
        'delivery_cost' => 'decimal:2',
        'actual_distance' => 'decimal:2'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(DeliveryStatusHistory::class);
    }

    public function updateStatus($status, $notes = null, $location = null)
    {
        $oldStatus = $this->status;
        $this->status = $status;
        $this->save();

        // تسجيل التغيير في السجل
        $history = new DeliveryStatusHistory([
            'delivery_schedule_id' => $this->id,
            'status' => $status,
            'notes' => $notes,
            'employee_id' => auth()->id()
        ]);

        if ($location) {
            $history->location_lat = $location['lat'];
            $history->location_lng = $location['lng'];
        }

        $history->save();

        // إرسال إشعارات حسب التغيير في الحالة
        switch ($status) {
            case 'assigned':
                event(new \App\Events\DeliveryAssigned($this));
                break;
            
            case 'out_for_delivery':
                event(new \App\Events\DeliveryStarted($this));
                break;
            
            case 'delivered':
                $this->actual_delivery_time = Carbon::now();
                $this->save();
                event(new \App\Events\DeliveryCompleted($this));
                break;
            
            case 'failed':
                event(new \App\Events\DeliveryFailed($this));
                break;
        }

        return true;
    }

    public function calculateDeliveryCost()
    {
        $zone = $this->zone;
        $cost = $zone->base_cost;

        if ($this->actual_distance && $zone->additional_cost_per_km) {
            $cost += $this->actual_distance * $zone->additional_cost_per_km;
        }

        $this->delivery_cost = $cost;
        $this->save();

        return $cost;
    }

    public static function getAvailableTimeSlots($date, $zoneId)
    {
        $timeSlots = DeliveryTimeSlot::where('is_active', true)->get();
        $deliveries = self::where('scheduled_date', $date)
            ->where('delivery_zone_id', $zoneId)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->groupBy('scheduled_time_slot');

        return $timeSlots->map(function ($slot) use ($deliveries) {
            $count = $deliveries->get($slot->name, collect())->count();
            return [
                'id' => $slot->id,
                'name' => $slot->name,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'available' => $count < $slot->max_deliveries,
                'remaining_slots' => $slot->max_deliveries - $count
            ];
        });
    }

    public function isDeliverable()
    {
        // التحقق من شروط التوصيل
        if (!$this->zone->is_active) {
            return false;
        }

        $invoice = $this->invoice;
        if ($invoice->total < $this->zone->minimum_order_value) {
            return false;
        }

        return true;
    }
} 