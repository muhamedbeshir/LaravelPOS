<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'notes'
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime'
    ];

    // العلاقات
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // الوظائف المساعدة
    public function getDuration()
    {
        if (!$this->check_out) {
            return null;
        }

        $totalMinutes = $this->check_in->diffInMinutes($this->check_out);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        if ($minutes > 0) {
            return number_format($hours + ($minutes / 60), 2);
        }
        
        return $hours;
    }

    public function isActive()
    {
        return $this->check_out === null;
    }
} 