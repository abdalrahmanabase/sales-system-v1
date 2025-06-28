<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'unit_id',
        'quantity',
        'purchase_price',
        'sell_price',
        'is_bonus',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'is_bonus' => 'boolean',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    /**
     * Get the actual cost for this item (0 if bonus, otherwise purchase_price)
     */
    public function getActualCostAttribute()
    {
        return $this->is_bonus ? 0 : $this->purchase_price;
    }

    /**
     * Get the total cost for this item (quantity * actual cost)
     */
    public function getTotalCostAttribute()
    {
        return $this->quantity * $this->actual_cost;
    }

    /**
     * Get the total purchase value (quantity * purchase_price, regardless of bonus status)
     */
    public function getTotalPurchaseValueAttribute()
    {
        return $this->quantity * $this->purchase_price;
    }
}
