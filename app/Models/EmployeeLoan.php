<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'loan_reference',
        'loan_type',
        'loan_amount',
        'interest_rate',
        'monthly_payment',
        'installments_count',
        'start_date',
        'end_date',
        'status',
        'total_paid',
        'remaining_balance',
        'purpose',
        'approval_notes',
        'approved_by',
        'approved_at',
        'guarantors',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'loan_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'approved_at' => 'datetime',
        'guarantors' => 'json',
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

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeeLoanPayment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByLoanType($query, $type)
    {
        return $query->where('loan_type', $type);
    }

    // Accessors
    public function getProgressPercentageAttribute(): float
    {
        if ($this->loan_amount <= 0) {
            return 0;
        }
        return ($this->total_paid / $this->loan_amount) * 100;
    }

    public function getNextPaymentDateAttribute(): ?Carbon
    {
        $lastPayment = $this->payments()->where('status', 'paid')->latest('payment_date')->first();
        
        if (!$lastPayment) {
            return Carbon::parse($this->start_date);
        }
        
        return Carbon::parse($lastPayment->payment_date)->addMonth();
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        
        $nextPaymentDate = $this->getNextPaymentDateAttribute();
        
        return $nextPaymentDate && $nextPaymentDate->lt(Carbon::now());
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->getIsOverdueAttribute()) {
            return 0;
        }
        
        $nextPaymentDate = $this->getNextPaymentDateAttribute();
        
        return $nextPaymentDate ? Carbon::now()->diffInDays($nextPaymentDate) : 0;
    }

    public function getRemainingPaymentsAttribute(): int
    {
        $paidPayments = $this->payments()->where('status', 'paid')->count();
        return max(0, $this->installments_count - $paidPayments);
    }

    // Helper Methods
    public function calculateMonthlyPayment(): float
    {
        if ($this->installments_count <= 0) {
            return 0;
        }
        
        $principal = $this->loan_amount;
        $rate = $this->interest_rate / 100 / 12; // Monthly interest rate
        $payments = $this->installments_count;
        
        if ($rate > 0) {
            // Calculate EMI using formula: EMI = P * r * (1 + r)^n / ((1 + r)^n - 1)
            $emi = $principal * $rate * pow((1 + $rate), $payments) / (pow((1 + $rate), $payments) - 1);
            return round($emi, 2);
        }
        
        // If no interest, simple division
        return round($principal / $payments, 2);
    }

    public function updateRemainingBalance(): void
    {
        $this->remaining_balance = $this->loan_amount - $this->total_paid;
        $this->save();
        
        // Check if loan is completed
        if ($this->remaining_balance <= 0) {
            $this->status = 'completed';
            $this->save();
        }
    }

    public function createPaymentSchedule(): array
    {
        $schedule = [];
        $startDate = Carbon::parse($this->start_date);
        $monthlyPayment = $this->monthly_payment;
        $remainingBalance = $this->loan_amount;
        $monthlyInterestRate = $this->interest_rate / 100 / 12;
        
        for ($i = 1; $i <= $this->installments_count; $i++) {
            $paymentDate = $startDate->copy()->addMonths($i - 1);
            $interestAmount = $remainingBalance * $monthlyInterestRate;
            $principalAmount = $monthlyPayment - $interestAmount;
            
            if ($principalAmount > $remainingBalance) {
                $principalAmount = $remainingBalance;
                $monthlyPayment = $principalAmount + $interestAmount;
            }
            
            $schedule[] = [
                'payment_number' => $i,
                'payment_date' => $paymentDate->format('Y-m-d'),
                'payment_amount' => round($monthlyPayment, 2),
                'principal_amount' => round($principalAmount, 2),
                'interest_amount' => round($interestAmount, 2),
                'remaining_balance' => round($remainingBalance - $principalAmount, 2),
            ];
            
            $remainingBalance -= $principalAmount;
            
            if ($remainingBalance <= 0) {
                break;
            }
        }
        
        return $schedule;
    }

    public function approve(User $approvedBy, string $notes = null): bool
    {
        $this->status = 'approved';
        $this->approved_by = $approvedBy->id;
        $this->approved_at = Carbon::now();
        $this->approval_notes = $notes;
        
        return $this->save();
    }

    public function activate(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }
        
        $this->status = 'active';
        return $this->save();
    }

    public function cancel(string $reason = null): bool
    {
        $this->status = 'cancelled';
        $this->approval_notes = $reason;
        return $this->save();
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($loan) {
            if (!$loan->loan_reference) {
                $loan->loan_reference = 'LN' . date('Y') . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
            
            if (!$loan->remaining_balance) {
                $loan->remaining_balance = $loan->loan_amount;
            }
        });
    }
}