<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'payroll_reference',
        'pay_period_start',
        'pay_period_end',
        'payment_date',
        'basic_salary',
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'allowances',
        'bonuses',
        'commissions',
        'gross_salary',
        'income_tax',
        'social_security',
        'health_insurance',
        'pension_contribution',
        'loan_deductions',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'amount_paid',
        'amount_remaining',
        'payment_method',
        'status',
        'payment_notes',
        'deduction_breakdown',
        'allowance_breakdown',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'allowances' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'commissions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'income_tax' => 'decimal:2',
        'social_security' => 'decimal:2',
        'health_insurance' => 'decimal:2',
        'pension_contribution' => 'decimal:2',
        'loan_deductions' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_remaining' => 'decimal:2',
        'deduction_breakdown' => 'json',
        'allowance_breakdown' => 'json',
        'processed_at' => 'datetime',
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

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('status', 'partially_paid');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByPayPeriod($query, $start, $end)
    {
        return $query->where('pay_period_start', '>=', $start)
                    ->where('pay_period_end', '<=', $end);
    }

    public function scopeByYear($query, $year)
    {
        return $query->whereYear('pay_period_start', $year);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('pay_period_start', $year)
                    ->whereMonth('pay_period_start', $month);
    }

    // Accessors
    public function getPayPeriodAttribute(): string
    {
        return $this->pay_period_start->format('M j') . ' - ' . $this->pay_period_end->format('M j, Y');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->payment_date && Carbon::parse($this->payment_date)->lt(Carbon::now());
    }

    public function getEffectiveTaxRateAttribute(): float
    {
        return $this->gross_salary > 0 ? ($this->income_tax / $this->gross_salary) * 100 : 0;
    }

    public function getDeductionRateAttribute(): float
    {
        return $this->gross_salary > 0 ? ($this->total_deductions / $this->gross_salary) * 100 : 0;
    }

    // Helper Methods
    public function calculateGrossSalary(): float
    {
        return $this->basic_salary + $this->overtime_amount + $this->allowances + $this->bonuses + $this->commissions;
    }

    public function calculateTotalDeductions(): float
    {
        return $this->income_tax + $this->social_security + $this->health_insurance + 
               $this->pension_contribution + $this->loan_deductions + $this->other_deductions;
    }

    public function calculateNetSalary(): float
    {
        return $this->gross_salary - $this->total_deductions;
    }

    public function calculateOvertimeAmount(): float
    {
        return $this->overtime_hours * $this->overtime_rate;
    }

    public function calculateIncomeTax(): float
    {
        $employee = $this->employee;
        if (!$employee || !$employee->tax_rate) {
            return 0;
        }
        
        return ($this->gross_salary * $employee->tax_rate) / 100;
    }

    public function calculateLoanDeductions(): float
    {
        return $this->employee->loans()
                    ->where('status', 'active')
                    ->sum('monthly_payment');
    }

    public function updateCalculations(): void
    {
        $this->overtime_amount = $this->calculateOvertimeAmount();
        $this->gross_salary = $this->calculateGrossSalary();
        $this->income_tax = $this->calculateIncomeTax();
        $this->loan_deductions = $this->calculateLoanDeductions();
        $this->total_deductions = $this->calculateTotalDeductions();
        $this->net_salary = $this->calculateNetSalary();
        $this->amount_remaining = $this->net_salary - $this->amount_paid;
    }

    public function markAsPaid(User $processedBy = null): bool
    {
        $this->status = 'paid';
        $this->amount_paid = $this->net_salary;
        $this->amount_remaining = 0;
        
        if ($processedBy) {
            $this->processed_by = $processedBy->id;
            $this->processed_at = Carbon::now();
        }
        
        return $this->save();
    }

    public function processPartialPayment(float $amount, User $processedBy = null): bool
    {
        if ($amount >= $this->amount_remaining) {
            return $this->markAsPaid($processedBy);
        }
        
        $this->amount_paid += $amount;
        $this->amount_remaining = $this->net_salary - $this->amount_paid;
        $this->status = 'partially_paid';
        
        if ($processedBy) {
            $this->processed_by = $processedBy->id;
            $this->processed_at = Carbon::now();
        }
        
        return $this->save();
    }

    public function generatePayslip(): array
    {
        return [
            'employee_name' => $this->employee->full_name,
            'employee_id' => $this->employee->employee_id,
            'payroll_reference' => $this->payroll_reference,
            'pay_period' => $this->getPayPeriodAttribute(),
            'payment_date' => $this->payment_date->format('M j, Y'),
            'earnings' => [
                'basic_salary' => $this->basic_salary,
                'overtime_hours' => $this->overtime_hours,
                'overtime_amount' => $this->overtime_amount,
                'allowances' => $this->allowances,
                'bonuses' => $this->bonuses,
                'commissions' => $this->commissions,
                'gross_salary' => $this->gross_salary,
            ],
            'deductions' => [
                'income_tax' => $this->income_tax,
                'social_security' => $this->social_security,
                'health_insurance' => $this->health_insurance,
                'pension_contribution' => $this->pension_contribution,
                'loan_deductions' => $this->loan_deductions,
                'other_deductions' => $this->other_deductions,
                'total_deductions' => $this->total_deductions,
            ],
            'net_salary' => $this->net_salary,
            'amount_paid' => $this->amount_paid,
            'amount_remaining' => $this->amount_remaining,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($salary) {
            if (!$salary->payroll_reference) {
                $salary->payroll_reference = 'PAY' . date('Y') . str_pad(static::max('id') + 1, 8, '0', STR_PAD_LEFT);
            }
        });
        
        static::saving(function ($salary) {
            $salary->updateCalculations();
        });
    }
}