<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name_id',
        'notes',
    ];

    public function companyName()
    {
        return $this->belongsTo(CompanyName::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(ProviderPayment::class);
    }

    public function providerSales()
    {
        return $this->hasMany(ProviderSale::class);
    }

    /**
     * Get total purchases amount for this provider
     */
    public function getTotalPurchasesAttribute()
    {
        return $this->purchaseInvoices()->sum('total_amount');
    }

    /**
     * Get total payments amount for this provider
     */
    public function getTotalPaymentsAttribute()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get current balance (total purchases - total payments)
     */
    public function getBalanceAttribute()
    {
        return $this->total_purchases - $this->total_payments;
    }

    /**
     * Check if provider has outstanding balance
     */
    public function hasOutstandingBalance()
    {
        return $this->balance > 0;
    }

    /**
     * Get balance for a specific invoice
     */
    public function getInvoiceBalance($invoiceId)
    {
        $invoice = $this->purchaseInvoices()->find($invoiceId);
        if (!$invoice) {
            return 0;
        }
        
        $paidAmount = $invoice->payments()->sum('amount');
        return $invoice->total_amount - $paidAmount;
    }

    /**
     * Get all invoices with their balances
     */
    public function getInvoicesWithBalances()
    {
        return $this->purchaseInvoices()->with('payments')->get()->map(function ($invoice) {
            $paidAmount = $invoice->payments->sum('amount');
            $balance = $invoice->total_amount - $paidAmount;
            
            return [
                'invoice' => $invoice,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'is_paid' => $balance <= 0,
            ];
        });
    }

    /**
     * Get payment history for this provider
     */
    public function getPaymentHistory()
    {
        return $this->payments()
            ->with('purchaseInvoice')
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get outstanding invoices (invoices with balance > 0)
     */
    public function getOutstandingInvoices()
    {
        return $this->purchaseInvoices()
            ->with('payments')
            ->get()
            ->filter(function ($invoice) {
                $paidAmount = $invoice->payments->sum('amount');
                return ($invoice->total_amount - $paidAmount) > 0;
            });
    }

    /**
     * Get total outstanding balance across all invoices
     */
    public function getTotalOutstandingBalance()
    {
        return $this->getOutstandingInvoices()->sum(function ($invoice) {
            $paidAmount = $invoice->payments->sum('amount');
            return $invoice->total_amount - $paidAmount;
        });
    }
}
