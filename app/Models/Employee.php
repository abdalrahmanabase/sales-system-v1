<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'employee_id',
        'national_id',
        'social_security_number',
        'passport_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'nationality',
        'marital_status',
        'phone',
        'email',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'job_title',
        'employee_type',
        'department',
        'salary',
        'hourly_rate',
        'working_hours_per_week',
        'hire_date',
        'probation_end_date',
        'contract_end_date',
        'employment_status',
        'termination_date',
        'termination_reason',
        'bank_account_number',
        'bank_name',
        'bank_routing_number',
        'tax_id',
        'tax_rate',
        'annual_leave_days',
        'sick_leave_days',
        'used_annual_leave',
        'used_sick_leave',
        'profile_picture',
        'notes',
        'is_active',
        'contact_info',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'date_of_birth' => 'date',
        'probation_end_date' => 'date',
        'contract_end_date' => 'date',
        'termination_date' => 'date',
        'salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function deviceLogs(): HasMany
    {
        return $this->hasMany(DeviceLog::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(EmployeeLeaveRequest::class);
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(EmployeeLoanPayment::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;
        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }
        if ($this->last_name) {
            $name .= ' ' . $this->last_name;
        }
        return $name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? Carbon::parse($this->date_of_birth)->age : null;
    }

    public function getYearsOfServiceAttribute(): ?int
    {
        return $this->hire_date ? Carbon::parse($this->hire_date)->diffInYears(Carbon::now()) : null;
    }

    public function getRemainingAnnualLeaveAttribute(): int
    {
        return $this->annual_leave_days - $this->used_annual_leave;
    }

    public function getRemainingSickLeaveAttribute(): int
    {
        return $this->sick_leave_days - $this->used_sick_leave;
    }

    public function getTotalActiveLoansAttribute(): float
    {
        return $this->loans()->whereIn('status', ['active', 'approved'])->sum('remaining_balance');
    }

    public function getMonthlyLoanDeductionsAttribute(): float
    {
        return $this->loans()->whereIn('status', ['active', 'approved'])->sum('monthly_payment');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('employment_status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false)->orWhere('employment_status', '!=', 'active');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByEmployeeType($query, $type)
    {
        return $query->where('employee_type', $type);
    }

    // Helper Methods
    public function calculateMonthlyGrossPayForHourlyEmployee($hoursWorked = null): float
    {
        if ($this->employee_type === 'full_time' && $this->salary) {
            return $this->salary;
        }

        if ($this->hourly_rate) {
            $hours = $hoursWorked ?? ($this->working_hours_per_week * 4.33); // Average weeks per month
            return $this->hourly_rate * $hours;
        }

        return 0;
    }

    public function calculateTaxDeduction($grossSalary): float
    {
        return ($grossSalary * $this->tax_rate) / 100;
    }

    public function isEligibleForLoan($amount): bool
    {
        $totalActiveLoans = $this->getTotalActiveLoansAttribute();
        $monthlyLoanDeductions = $this->getMonthlyLoanDeductionsAttribute();
        $proposedMonthlyPayment = $amount / 12; // Assuming 12 months default
        
        // Check if total monthly deductions don't exceed 50% of salary
        $maxAllowedDeduction = $this->salary * 0.5;
        
        return ($monthlyLoanDeductions + $proposedMonthlyPayment) <= $maxAllowedDeduction;
    }

    public function hasOutstandingLoans(): bool
    {
        return $this->loans()->whereIn('status', ['active', 'approved'])->exists();
    }

    public function getMonthlyAttendanceData($year, $month)
    {
        return $this->attendance()
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();
    }

    public function calculateOvertimeHours($year, $month)
    {
        return $this->attendance()
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->sum('overtime_hours');
    }
}
