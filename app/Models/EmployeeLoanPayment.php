<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeLoanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_loan_id',
        'employee_id',
        'payment_reference',
        'payment_amount',
        'principal_amount',
        'interest_amount',
        'payment_date',
        'due_date',
        'payment_method',
        'status',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
    ];

    // Relationships
    public function employeeLoan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByLoan($query, $loanId)
    {
        return $query->where('employee_loan_id', $loanId);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date && Carbon::parse($this->due_date)->lt(Carbon::now());
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->getIsOverdueAttribute()) {
            return 0;
        }
        
        return Carbon::now()->diffInDays(Carbon::parse($this->due_date));
    }

    // Helper Methods
    public function markAsPaid(User $processedBy = null): bool
    {
        $this->status = 'paid';
        $this->payment_date = Carbon::now();
        
        if ($processedBy) {
            $this->processed_by = $processedBy->id;
        }
        
        $saved = $this->save();
        
        if ($saved) {
            // Update loan total paid and remaining balance
            $this->employeeLoan->total_paid += $this->payment_amount;
            $this->employeeLoan->updateRemainingBalance();
        }
        
        return $saved;
    }

    public function markAsOverdue(): bool
    {
        if ($this->status === 'pending' && $this->getIsOverdueAttribute()) {
            $this->status = 'overdue';
            return $this->save();
        }
        
        return false;
    }

    public function processPartialPayment(float $amount, User $processedBy = null): bool
    {
        if ($amount >= $this->payment_amount) {
            return $this->markAsPaid($processedBy);
        }
        
        $this->status = 'partially_paid';
        $this->payment_amount -= $amount;
        $this->principal_amount = $this->payment_amount - $this->interest_amount;
        
        if ($processedBy) {
            $this->processed_by = $processedBy->id;
        }
        
        $saved = $this->save();
        
        if ($saved) {
            // Update loan total paid
            $this->employeeLoan->total_paid += $amount;
            $this->employeeLoan->updateRemainingBalance();
        }
        
        return $saved;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (!$payment->payment_reference) {
                $payment->payment_reference = 'LP' . date('Y') . str_pad(static::max('id') + 1, 8, '0', STR_PAD_LEFT);
            }
        });
        
        static::updating(function ($payment) {
            // Check if payment is overdue when updating
            if ($payment->isDirty('due_date') || $payment->isDirty('status')) {
                if ($payment->getIsOverdueAttribute() && $payment->status === 'pending') {
                    $payment->status = 'overdue';
                }
            }
        });
    }
}