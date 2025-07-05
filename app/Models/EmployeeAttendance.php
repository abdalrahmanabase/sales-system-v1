<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'attendance_date',
        'scheduled_in_time',
        'scheduled_out_time',
        'check_in_time',
        'check_out_time',
        'status',
        'regular_hours',
        'overtime_hours',
        'break_hours',
        'total_hours',
        'notes',
        'leave_type',
        'is_holiday',
        'location',
        'break_times',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'scheduled_in_time' => 'datetime',
        'scheduled_out_time' => 'datetime',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'break_hours' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'is_holiday' => 'boolean',
        'break_times' => 'json',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('attendance_date', [$start, $end]);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('attendance_date', $year)
                    ->whereMonth('attendance_date', $month);
    }

    public function scopeOnLeave($query)
    {
        return $query->whereNotNull('leave_type');
    }

    public function scopeWorkedOvertime($query)
    {
        return $query->where('overtime_hours', '>', 0);
    }

    public function scopeHolidays($query)
    {
        return $query->where('is_holiday', true);
    }

    // Accessors
    public function getIsLateAttribute(): bool
    {
        if (!$this->check_in_time || !$this->scheduled_in_time) {
            return false;
        }
        
        return Carbon::parse($this->check_in_time)->gt(Carbon::parse($this->scheduled_in_time));
    }

    public function getIsEarlyDepartureAttribute(): bool
    {
        if (!$this->check_out_time || !$this->scheduled_out_time) {
            return false;
        }
        
        return Carbon::parse($this->check_out_time)->lt(Carbon::parse($this->scheduled_out_time));
    }

    public function getMinutesLateAttribute(): int
    {
        if (!$this->getIsLateAttribute()) {
            return 0;
        }
        
        return Carbon::parse($this->check_in_time)->diffInMinutes(Carbon::parse($this->scheduled_in_time));
    }

    public function getMinutesEarlyDepartureAttribute(): int
    {
        if (!$this->getIsEarlyDepartureAttribute()) {
            return 0;
        }
        
        return Carbon::parse($this->scheduled_out_time)->diffInMinutes(Carbon::parse($this->check_out_time));
    }

    public function getWorkedHoursAttribute(): float
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return 0;
        }
        
        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);
        
        return $checkOut->diffInMinutes($checkIn) / 60;
    }

    public function getScheduledHoursAttribute(): float
    {
        if (!$this->scheduled_in_time || !$this->scheduled_out_time) {
            return 0;
        }
        
        $scheduledIn = Carbon::parse($this->scheduled_in_time);
        $scheduledOut = Carbon::parse($this->scheduled_out_time);
        
        return $scheduledOut->diffInMinutes($scheduledIn) / 60;
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->check_in_time && $this->check_out_time;
    }

    public function getIsOnLeaveAttribute(): bool
    {
        return !is_null($this->leave_type);
    }

    // Helper Methods
    public function calculateHours(): void
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return;
        }

        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);
        
        // Calculate total worked hours
        $totalMinutes = $checkOut->diffInMinutes($checkIn);
        $this->total_hours = $totalMinutes / 60;
        
        // Subtract break time
        $this->total_hours -= $this->break_hours;
        
        // Calculate regular and overtime hours
        $scheduledHours = $this->getScheduledHoursAttribute();
        
        if ($this->total_hours <= $scheduledHours) {
            $this->regular_hours = $this->total_hours;
            $this->overtime_hours = 0;
        } else {
            $this->regular_hours = $scheduledHours;
            $this->overtime_hours = $this->total_hours - $scheduledHours;
        }
    }

    public function calculateStatus(): void
    {
        if ($this->is_holiday || $this->getIsOnLeaveAttribute()) {
            return; // Don't change status for holidays or leave
        }

        if (!$this->check_in_time) {
            $this->status = 'absent';
            return;
        }

        if ($this->getIsLateAttribute()) {
            $this->status = 'late';
            return;
        }

        $this->status = 'present';
    }

    public function checkIn(Carbon $time = null, string $location = null): bool
    {
        if ($this->check_in_time) {
            return false; // Already checked in
        }

        $this->check_in_time = $time ?? Carbon::now();
        $this->location = $location;
        
        $this->calculateStatus();
        
        return $this->save();
    }

    public function checkOut(Carbon $time = null): bool
    {
        if (!$this->check_in_time || $this->check_out_time) {
            return false; // Not checked in or already checked out
        }

        $this->check_out_time = $time ?? Carbon::now();
        
        $this->calculateHours();
        
        return $this->save();
    }

    public function addBreak(Carbon $startTime, Carbon $endTime): void
    {
        $breakTimes = $this->break_times ?? [];
        
        $breakTimes[] = [
            'start' => $startTime->toISOString(),
            'end' => $endTime->toISOString(),
            'duration' => $endTime->diffInMinutes($startTime)
        ];
        
        $this->break_times = $breakTimes;
        $this->break_hours = collect($breakTimes)->sum('duration') / 60;
        
        $this->calculateHours();
    }

    public function getTotalBreakMinutes(): int
    {
        if (!$this->break_times) {
            return 0;
        }
        
        return collect($this->break_times)->sum('duration');
    }

    public function isWorkingDay(): bool
    {
        return !$this->is_holiday && !$this->getIsOnLeaveAttribute();
    }

    public function getProductivityRate(): float
    {
        if (!$this->getScheduledHoursAttribute()) {
            return 0;
        }
        
        $actualProductiveHours = $this->total_hours;
        $scheduledHours = $this->getScheduledHoursAttribute();
        
        return ($actualProductiveHours / $scheduledHours) * 100;
    }

    public function getAttendanceScore(): float
    {
        $score = 100;
        
        if ($this->status === 'absent') {
            return 0;
        }
        
        if ($this->status === 'late') {
            $score -= min(30, $this->getMinutesLateAttribute() / 2); // Deduct up to 30 points
        }
        
        if ($this->getIsEarlyDepartureAttribute()) {
            $score -= min(20, $this->getMinutesEarlyDepartureAttribute() / 3); // Deduct up to 20 points
        }
        
        return max(0, $score);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($attendance) {
            if ($attendance->isDirty(['check_in_time', 'check_out_time'])) {
                $attendance->calculateHours();
                $attendance->calculateStatus();
            }
        });
    }
}
