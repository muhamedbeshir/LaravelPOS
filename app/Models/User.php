<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'username',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * تحديد ما إذا كان المستخدم هو المستخدم الرئيسي الذي له كل الصلاحيات
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * العلاقة مع الورديات التي يديرها المستخدم كموظف رئيسي
     */
    public function managedShifts()
    {
        return $this->hasMany(Shift::class, 'main_cashier_id');
    }

    /**
     * العلاقة مع الورديات التي شارك فيها المستخدم
     */
    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'shift_users')
            ->withPivot('join_time', 'leave_time')
            ->withTimestamps();
    }

    /**
     * العلاقة مع عمليات السحب من الوردية
     */
    public function shiftWithdrawals()
    {
        return $this->hasMany(ShiftWithdrawal::class);
    }

    /**
     * التحقق من وجود وردية مفتوحة للمستخدم الحالي
     */
    public function hasOpenShift()
    {
        return $this->shifts()
            ->where('is_closed', false)
            ->exists();
    }

    /**
     * جلب الوردية المفتوحة للمستخدم الحالي
     */
    public function getCurrentShift()
    {
        return $this->shifts()
            ->where('is_closed', false)
            ->latest()
            ->first();
    }
}
