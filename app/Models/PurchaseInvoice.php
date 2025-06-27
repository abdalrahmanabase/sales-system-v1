<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'warehouse_id',
        'branch_id',
        'invoice_number',
        'invoice_date',
        'image_path',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(ProviderPayment::class);
    }

    /**
     * Get total paid amount for this invoice
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get current balance for this invoice
     */
    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->total_paid;
    }

    /**
     * Check if invoice is fully paid
     */
    public function isFullyPaid()
    {
        return $this->balance <= 0;
    }

    /**
     * Check if invoice has outstanding balance
     */
    public function hasOutstandingBalance()
    {
        return $this->balance > 0;
    }

    /**
     * Get payment percentage
     */
    public function getPaymentPercentageAttribute()
    {
        if ($this->total_amount == 0) {
            return 100;
        }
        
        return min(100, ($this->total_paid / $this->total_amount) * 100);
    }
}
