<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'abbreviation',
        'conversion_factor',
        'sell_price',
        'purchase_price',
        'is_base_unit',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'sell_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'is_base_unit' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Get the base unit for this product
    public function getBaseUnitAttribute()
    {
        return $this->product->productUnits()->where('is_base_unit', true)->first();
    }

    // Convert quantity from this unit to base unit
    public function convertToBaseUnit($quantity)
    {
        return $quantity * $this->conversion_factor;
    }

    // Convert quantity from base unit to this unit
    public function convertFromBaseUnit($quantity)
    {
        return $quantity / $this->conversion_factor;
    }

    // Get effective sell price (converted to base unit if needed)
    public function getEffectiveSellPriceAttribute()
    {
        if ($this->is_base_unit) {
            return $this->sell_price;
        }
        
        $baseUnit = $this->getBaseUnitAttribute();
        if ($baseUnit) {
            return $this->sell_price / $this->conversion_factor;
        }
        
        return $this->sell_price;
    }

    // Get effective purchase price (converted to base unit if needed)
    public function getEffectivePurchasePriceAttribute()
    {
        if ($this->is_base_unit) {
            return $this->purchase_price;
        }
        
        $baseUnit = $this->getBaseUnitAttribute();
        if ($baseUnit) {
            return $this->purchase_price / $this->conversion_factor;
        }
        
        return $this->purchase_price;
    }

    // Scope to get only active units
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope to get base units
    public function scopeBaseUnit($query)
    {
        return $query->where('is_base_unit', true);
    }
}
