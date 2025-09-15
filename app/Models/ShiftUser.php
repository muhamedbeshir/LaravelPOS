<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ShiftUser extends Pivot
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'join_time' => 'datetime',
        'leave_time' => 'datetime',
    ];
} 