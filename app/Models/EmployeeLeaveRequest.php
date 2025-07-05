<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeLeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'request_reference',
        'leave_type',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'manager_notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'attachment',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByLeaveType($query, $type)
    {
        return $query->where('leave_type', $type);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->where(function($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
              ->orWhereBetween('end_date', [$start, $end])
              ->orWhere(function($q2) use ($start, $end) {
                  $q2->where('start_date', '<=', $start)
                     ->where('end_date', '>=', $end);
              });
        });
    }

    // Accessors
    public function getLeavePeriodAttribute(): string
    {
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('M j, Y');
        }
        
        return $this->start_date->format('M j') . ' - ' . $this->end_date->format('M j, Y');
    }

    public function getIsCurrentAttribute(): bool
    {
        $today = Carbon::now();
        return $this->status === 'approved' && 
               $this->start_date->lte($today) && 
               $this->end_date->gte($today);
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->status === 'approved' && $this->start_date->gt(Carbon::now());
    }

    public function getIsPastAttribute(): bool
    {
        return $this->status === 'approved' && $this->end_date->lt(Carbon::now());
    }

    public function getDaysUntilStartAttribute(): int
    {
        if ($this->start_date->lte(Carbon::now())) {
            return 0;
        }
        
        return Carbon::now()->diffInDays($this->start_date);
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->end_date->lt(Carbon::now())) {
            return 0;
        }
        
        if ($this->start_date->gt(Carbon::now())) {
            return $this->days_requested;
        }
        
        return Carbon::now()->diffInDays($this->end_date) + 1;
    }

    // Helper Methods
    public function calculateBusinessDays(): int
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $businessDays = 0;

        while ($startDate->lte($endDate)) {
            if ($startDate->isWeekday()) {
                $businessDays++;
            }
            $startDate->addDay();
        }

        return $businessDays;
    }

    public function approve(User $approvedBy, string $notes = null): bool
    {
        // Check if employee has enough leave balance
        if (!$this->checkLeaveBalance()) {
            return false;
        }

        $this->status = 'approved';
        $this->approved_by = $approvedBy->id;
        $this->approved_at = Carbon::now();
        $this->manager_notes = $notes;

        $saved = $this->save();

        if ($saved) {
            $this->deductLeaveBalance();
        }

        return $saved;
    }

    public function reject(User $rejectedBy, string $reason): bool
    {
        $this->status = 'rejected';
        $this->rejected_by = $rejectedBy->id;
        $this->rejected_at = Carbon::now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    public function cancel(): bool
    {
        if ($this->status === 'approved') {
            $this->refundLeaveBalance();
        }

        $this->status = 'cancelled';
        return $this->save();
    }

    public function checkLeaveBalance(): bool
    {
        $employee = $this->employee;
        
        switch ($this->leave_type) {
            case 'annual':
                return $employee->remaining_annual_leave >= $this->days_requested;
            case 'sick':
                return $employee->remaining_sick_leave >= $this->days_requested;
            case 'maternity':
            case 'paternity':
            case 'emergency':
            case 'study':
            case 'compassionate':
                return true; // These usually don't count against annual leave
            case 'unpaid':
                return true; // Unpaid leave doesn't require balance
            default:
                return false;
        }
    }

    public function deductLeaveBalance(): void
    {
        $employee = $this->employee;
        
        switch ($this->leave_type) {
            case 'annual':
                $employee->used_annual_leave += $this->days_requested;
                break;
            case 'sick':
                $employee->used_sick_leave += $this->days_requested;
                break;
        }
        
        $employee->save();
    }

    public function refundLeaveBalance(): void
    {
        $employee = $this->employee;
        
        switch ($this->leave_type) {
            case 'annual':
                $employee->used_annual_leave = max(0, $employee->used_annual_leave - $this->days_requested);
                break;
            case 'sick':
                $employee->used_sick_leave = max(0, $employee->used_sick_leave - $this->days_requested);
                break;
        }
        
        $employee->save();
    }

    public function hasConflictingLeave(): bool
    {
        return static::where('employee_id', $this->employee_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'approved')
            ->where(function($query) {
                $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                      ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                      ->orWhere(function($q) {
                          $q->where('start_date', '<=', $this->start_date)
                            ->where('end_date', '>=', $this->end_date);
                      });
            })
            ->exists();
    }

    public function getRequiredAttachment(): bool
    {
        return in_array($this->leave_type, ['sick', 'maternity', 'paternity', 'medical']);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($leave) {
            if (!$leave->request_reference) {
                $leave->request_reference = 'LR' . date('Y') . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
            
            if (!$leave->days_requested) {
                $leave->days_requested = $leave->calculateBusinessDays();
            }
        });
    }
}