<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'default_due_days',
        'enable_whatsapp_notifications',
        'send_invoice_notifications',
        'send_due_date_reminders',
        'reminder_days_before'
    ];

    protected $casts = [
        'enable_whatsapp_notifications' => 'boolean',
        'send_invoice_notifications' => 'boolean',
        'send_due_date_reminders' => 'boolean',
        'reminder_days_before' => 'integer',
        'default_due_days' => 'integer'
    ];
}
