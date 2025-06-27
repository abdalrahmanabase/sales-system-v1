<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'barcode',
        'category_id',
        'subcategory_id',
        'provider_id',
        'purchase_price_per_unit',
        'sell_price_per_unit',
        'stock',
        'low_stock_threshold',
        'is_active',
    ];

    protected $casts = [
        'purchase_price_per_unit' => 'decimal:2',
        'sell_price_per_unit' => 'decimal:2',
        'is_active' => 'boolean',
        'low_stock_threshold' => 'integer',
    ];

    // Main category (parent category)
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Subcategory (child category)
    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    // Get the effective category (subcategory if exists, otherwise main category)
    public function getEffectiveCategoryAttribute()
    {
        return $this->subcategory ?? $this->category;
    }

    // Check if this is a subcategory of the given parent category
    public function isSubcategoryOf($parentCategoryId)
    {
        return $this->subcategory && $this->subcategory->parent_id == $parentCategoryId;
    }

    // Check if product is low on stock
    public function isLowStock()
    {
        return $this->stock <= $this->low_stock_threshold;
    }

    // Check if product is out of stock
    public function isOutOfStock()
    {
        return $this->stock <= 0;
    }

    // Get stock status (normal, low, out)
    public function getStockStatusAttribute()
    {
        if ($this->isOutOfStock()) {
            return 'out';
        } elseif ($this->isLowStock()) {
            return 'low';
        } else {
            return 'normal';
        }
    }

    // Get stock status color for display
    public function getStockStatusColorAttribute()
    {
        switch ($this->stock_status) {
            case 'out':
                return 'danger';
            case 'low':
                return 'warning';
            default:
                return 'success';
        }
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function productStocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function purchaseInvoiceItems()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function callOrderItems()
    {
        return $this->hasMany(CallOrderItem::class);
    }

    public function offerConditions()
    {
        return $this->hasMany(OfferCondition::class);
    }

    public function offerRewards()
    {
        return $this->hasMany(OfferReward::class);
    }

    public function priceHistories()
    {
        return $this->hasMany(ProductPriceHistory::class)->orderBy('changed_at', 'desc');
    }

    // Helper method to record price change history
    public function recordPriceChange($oldPurchasePrice, $newPurchasePrice, $oldSellPrice, $newSellPrice, $reason = 'manual_update', $notes = null)
    {
        return $this->priceHistories()->create([
            'old_purchase_price' => $oldPurchasePrice,
            'new_purchase_price' => $newPurchasePrice,
            'old_sell_price' => $oldSellPrice,
            'new_sell_price' => $newSellPrice,
            'changed_at' => now(),
            'changed_by' => auth()->id(),
            'change_reason' => $reason,
            'notes' => $notes,
        ]);
    }

    // Helper method to get product by barcode
    public static function findByBarcode($barcode)
    {
        return static::where('barcode', $barcode)->first();
    }

    // Helper method to create or update product from barcode
    public static function createOrUpdateFromBarcode($barcode, $data = [])
    {
        $product = static::findByBarcode($barcode);
        
        if ($product) {
            // Update existing product
            $product->update($data);
            return $product;
        } else {
            // Create new product
            $data['barcode'] = $barcode;
            return static::create($data);
        }
    }

    // Scope to get products by parent category
    public function scopeByParentCategory($query, $parentCategoryId)
    {
        return $query->where(function ($q) use ($parentCategoryId) {
            $q->where('category_id', $parentCategoryId)
              ->orWhereHas('subcategory', function ($subQ) use ($parentCategoryId) {
                  $subQ->where('parent_id', $parentCategoryId);
              });
        });
    }

    // Scope to get products by subcategory
    public function scopeBySubcategory($query, $subcategoryId)
    {
        return $query->where('subcategory_id', $subcategoryId);
    }

    // Scope to get low stock products
    public function scopeLowStock($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw('stock <= low_stock_threshold')
              ->where('stock', '>', 0);
        });
    }

    // Scope to get out of stock products
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    // Scope to get products with custom low stock threshold
    public function scopeWithCustomLowStock($query, $threshold)
    {
        if (is_numeric($threshold) && $threshold >= 0) {
            return $query->where('low_stock_threshold', '>', (int) $threshold);
        }
        return $query;
    }
}
