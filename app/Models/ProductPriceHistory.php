<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'old_purchase_price',
        'new_purchase_price',
        'old_sell_price',
        'new_sell_price',
        'changed_at',
        'changed_by',
        'change_reason',
        'source_type',
        'source_id',
        'source_reference',
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

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class);
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

    // Get price change percentage for purchase price
    public function getPurchasePriceChangePercentageAttribute()
    {
        if ($this->old_purchase_price == 0) {
            return $this->new_purchase_price > 0 ? 100 : 0;
        }
        
        return (($this->new_purchase_price - $this->old_purchase_price) / $this->old_purchase_price) * 100;
    }

    // Get price change percentage for sell price
    public function getSellPriceChangePercentageAttribute()
    {
        if ($this->old_sell_price == 0) {
            return $this->new_sell_price > 0 ? 100 : 0;
        }
        
        return (($this->new_sell_price - $this->old_sell_price) / $this->old_sell_price) * 100;
    }

    // Get price change direction
    public function getPriceChangeDirectionAttribute()
    {
        if ($this->purchase_price_changed && $this->sell_price_changed) {
            $purchaseDirection = $this->new_purchase_price > $this->old_purchase_price ? 'up' : 'down';
            $sellDirection = $this->new_sell_price > $this->old_sell_price ? 'up' : 'down';
            
            if ($purchaseDirection === $sellDirection) {
                return $purchaseDirection;
            } else {
                return 'mixed';
            }
        } elseif ($this->purchase_price_changed) {
            return $this->new_purchase_price > $this->old_purchase_price ? 'up' : 'down';
        } elseif ($this->sell_price_changed) {
            return $this->new_sell_price > $this->old_sell_price ? 'up' : 'down';
        }
        
        return 'none';
    }

    // Scope to get price changes by reason
    public function scopeByReason($query, $reason)
    {
        return $query->where('change_reason', $reason);
    }

    // Scope to get price changes by source type
    public function scopeBySourceType($query, $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    // Scope to get price changes for a date range
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('changed_at', [$startDate, $endDate]);
    }

    // Scope to get price changes for a specific unit
    public function scopeForUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    // Scope to get price changes for base units only
    public function scopeBaseUnitsOnly($query)
    {
        return $query->whereHas('unit', function ($q) {
            $q->where('is_base_unit', true);
        });
    }

    // Get price history summary for a date range
    public static function getPriceHistorySummary($productId, $startDate, $endDate, $unitId = null)
    {
        $query = static::where('product_id', $productId)
            ->forDateRange($startDate, $endDate);
        
        if ($unitId) {
            $query->forUnit($unitId);
        }
        
        $changes = $query->orderBy('changed_at', 'desc')->get();
        
        $summary = [
            'total_changes' => $changes->count(),
            'purchase_price_changes' => $changes->where('purchase_price_changed', true)->count(),
            'sell_price_changes' => $changes->where('sell_price_changed', true)->count(),
            'price_increases' => $changes->where('price_change_direction', 'up')->count(),
            'price_decreases' => $changes->where('price_change_direction', 'down')->count(),
            'average_purchase_change' => $changes->where('purchase_price_changed', true)->avg('purchase_price_change_percentage'),
            'average_sell_change' => $changes->where('sell_price_changed', true)->avg('sell_price_change_percentage'),
            'changes_by_reason' => $changes->groupBy('change_reason')->map->count(),
            'changes_by_source' => $changes->groupBy('source_type')->map->count(),
        ];
        
        return $summary;
    }
}
