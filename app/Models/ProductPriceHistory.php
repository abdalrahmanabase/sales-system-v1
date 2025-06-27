<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'old_purchase_price',
        'new_purchase_price',
        'old_sell_price',
        'new_sell_price',
        'changed_at',
        'changed_by',
        'change_reason',
        'notes',
    ];

    protected $casts = [
        'old_purchase_price' => 'decimal:2',
        'new_purchase_price' => 'decimal:2',
        'old_sell_price' => 'decimal:2',
        'new_sell_price' => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // Helper method to check if purchase price changed
    public function getPurchasePriceChangedAttribute()
    {
        return $this->old_purchase_price !== $this->new_purchase_price;
    }

    // Helper method to check if sell price changed
    public function getSellPriceChangedAttribute()
    {
        return $this->old_sell_price !== $this->new_sell_price;
    }

    // Helper method to get price change description
    public function getPriceChangeDescriptionAttribute()
    {
        $changes = [];
        
        if ($this->purchase_price_changed) {
            $changes[] = "Purchase: \${$this->old_purchase_price} → \${$this->new_purchase_price}";
        }
        
        if ($this->sell_price_changed) {
            $changes[] = "Sell: \${$this->old_sell_price} → \${$this->new_sell_price}";
        }
        
        return implode(', ', $changes);
    }
}
