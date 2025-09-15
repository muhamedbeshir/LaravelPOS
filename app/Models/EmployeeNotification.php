<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'title',
        'message',
        'type',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    // العلاقات
    public function employee()
    {
        return $this->belongsTo(Employee::class);
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
            'salary_due' => 'danger',
            'salary_paid' => 'success',
            'attendance' => 'info',
            'warning' => 'warning'
        ];

        return $classes[$this->type] ?? 'primary';
    }
} 