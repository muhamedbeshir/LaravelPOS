<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'employee_number',
        'address',
        'national_id',
        'salary',
        'job_title_id',
        'credit_balance',
        'is_active'
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // العلاقات
    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function salaryPayments()
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function notifications()
    {
        return $this->hasMany(EmployeeNotification::class);
    }

    public function deliveryInvoices()
    {
        return $this->hasMany(Invoice::class, 'delivery_employee_id');
    }

    /**
     * علاقة السلف الخاصة بالموظف
     */
    public function advances()
    {
        return $this->hasMany(EmployeeAdvance::class);
    }

    /**
     * علاقة المستخدم المرتبط بالموظف
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }

    // الوظائف المساعدة
    public function getCurrentMonthSalaryPayment()
    {
        return $this->salaryPayments()
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->first();
    }

    /**
     * Get salary payment for a specific year and month
     *
     * @param int $year
     * @param int $month
     * @return \App\Models\SalaryPayment|null
     */
    public function getSalaryPaymentForMonth($year, $month)
    {
        return $this->salaryPayments()
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->first();
    }

    public function isSalaryPaid()
    {
        return $this->getCurrentMonthSalaryPayment() !== null;
    }

    public function getStatusClass()
    {
        return $this->isSalaryPaid() ? 'text-success' : 'text-danger';
    }

    public function getStatusText()
    {
        return $this->isSalaryPaid() ? 'تم دفع الراتب' : 'لم يتم دفع الراتب';
    }

    public function getTodayAttendance()
    {
        return $this->attendanceRecords()
            ->whereDate('date', today())
            ->latest('check_in')
            ->first();
    }

    public function isCheckedIn()
    {
        $record = $this->getTodayAttendance();
        return $record !== null && $record->check_out === null;
    }

    /**
     * Calculate the average working hours for the employee.
     *
     * @return float
     */
    public function averageWorkingHours(): float
    {
        $totalMinutes = $this->attendanceRecords()
            ->whereNotNull('check_out')
            ->get()
            ->sum(function ($record) {
                return $record->check_in->diffInMinutes($record->check_out);
            });

        $attendanceDays = $this->attendanceRecords()->whereNotNull('check_out')->count();

        if ($attendanceDays === 0) {
            return 0;
        }

        return ($totalMinutes / $attendanceDays) / 60;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // إدارة الرصيد
    public function addToBalance($amount)
    {
        $this->update(['credit_balance' => $this->credit_balance + $amount]);
    }

    public function deductFromBalance($amount)
    {
        $this->update(['credit_balance' => $this->credit_balance - $amount]);
    }

    public function getNextPaymentDate()
    {
        $frequency = Setting::get('salary_display_frequency', 'monthly');
        $lastPayment = $this->salaryPayments()->latest('payment_date')->first();
        $currentDate = \Carbon\Carbon::now();

        if ($frequency === 'monthly') {
            if ($lastPayment) {
                $nextPaymentDate = $lastPayment->payment_date->copy()->addMonth();
                // Ensure next payment date is in the future
                if ($nextPaymentDate->isPast()) {
                    $nextPaymentDate = $currentDate->copy()->addMonth()->endOfMonth();
                }
                return $nextPaymentDate;
            } else {
                return $currentDate->copy()->endOfMonth();
            }
        } elseif ($frequency === 'weekly') {
            if ($lastPayment) {
                $nextPaymentDate = $lastPayment->payment_date->copy()->addWeek();
                 // Ensure next payment date is in the future
                if ($nextPaymentDate->isPast()) {
                    $nextPaymentDate = $currentDate->copy()->addWeek()->endOfWeek();
                }
                return $nextPaymentDate;
            } else {
                return $currentDate->copy()->endOfWeek();
            }
        } else {
            return null; // Handle invalid frequency
        }
    }
} 