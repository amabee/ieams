<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'employee_no', 'first_name', 'last_name', 'middle_name',
        'position_id', 'employment_type', 'branch_id', 'shift_id',
        'hire_date', 'status', 'photo_path', 'contact_no', 'address',
        'birthdate', 'gender', 'civil_status',
        'basic_salary', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin_no',
    ];

    protected $casts = [
        'hire_date'    => 'date',
        'birthdate'    => 'date',
        'basic_salary' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function currentSchedule()
    {
        return $this->schedules()
            ->where('effective_date', '<=', today())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', today());
            })
            ->latest('effective_date')
            ->first();
    }
}
