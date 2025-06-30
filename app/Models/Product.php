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
        'base_unit_id',
    ];

    protected $casts = [
        'purchase_price_per_unit' => 'decimal:2',
        'sell_price_per_unit' => 'decimal:2',
        'is_active' => 'boolean',
        'low_stock_threshold' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // Create default "Piece" unit when a product is created
        static::created(function ($product) {
            // Record initial creation in price history
            $product->recordPriceChange(
                0, // No old purchase price (initial creation)
                $product->purchase_price_per_unit ?? 0,
                0, // No old sell price (initial creation)
                $product->sell_price_per_unit ?? 0,
                'product_creation',
                'Product created with initial prices',
                $product->base_unit_id,
                'creation',
                null,
                'Product Creation'
            );
        });
    }

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

    public function baseUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'base_unit_id');
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
    public function recordPriceChange($oldPurchasePrice, $newPurchasePrice, $oldSellPrice, $newSellPrice, $reason = 'manual_update', $notes = null, $unitId = null, $sourceType = null, $sourceId = null, $sourceReference = null)
    {
        return $this->priceHistories()->create([
            'unit_id' => $unitId,
            'old_purchase_price' => $oldPurchasePrice,
            'new_purchase_price' => $newPurchasePrice,
            'old_sell_price' => $oldSellPrice,
            'new_sell_price' => $newSellPrice,
            'changed_at' => now(),
            'changed_by' => auth()->id(),
            'change_reason' => $reason,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_reference' => $sourceReference,
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

    // Get base unit for this product
    public function getBaseUnit()
    {
        return $this->productUnits()->where('is_base_unit', true)->first();
    }

    // Get active units for this product
    public function getActiveUnits()
    {
        return $this->productUnits()->where('is_active', true)->get();
    }

    // Helper method to ensure default unit exists
    public function ensureDefaultUnitExists()
    {
        // Check if a default unit already exists
        $existingDefaultUnit = $this->productUnits()
            ->where('name', 'Piece')
            ->where('abbreviation', 'pcs')
            ->where('conversion_factor', 1)
            ->where('is_base_unit', true)
            ->first();
            
        if ($existingDefaultUnit) {
            // Update product's base_unit_id if not set
            if (!$this->base_unit_id) {
                $this->update(['base_unit_id' => $existingDefaultUnit->id]);
            }
            return $existingDefaultUnit;
        }
        
        // Check if any units exist at all
        if ($this->productUnits()->count() === 0) {
            $defaultUnit = $this->productUnits()->create([
                'name' => 'Piece',
                'abbreviation' => 'pcs',
                'conversion_factor' => 1,
                'purchase_price' => $this->purchase_price_per_unit ?? 0,
                'sell_price' => $this->sell_price_per_unit ?? 0,
                'is_base_unit' => true,
                'is_active' => true,
            ]);

            $this->update(['base_unit_id' => $defaultUnit->id]);
            return $defaultUnit;
        }
        
        // If units exist but no default, make the first one the default
        $firstUnit = $this->productUnits()->first();
        $firstUnit->update([
            'name' => 'Piece',
            'abbreviation' => 'pcs',
            'conversion_factor' => 1,
            'is_base_unit' => true,
            'is_active' => true,
        ]);
        
        $this->update(['base_unit_id' => $firstUnit->id]);
        return $firstUnit;
    }

    // Helper method to update unit prices based on base price changes
    public function updateUnitPricesFromBase()
    {
        $baseUnit = $this->getBaseUnit();
        if ($baseUnit) {
            foreach ($this->productUnits as $unit) {
                if ($unit->id !== $baseUnit->id) {
                    $unit->update([
                        'purchase_price' => $this->purchase_price_per_unit * $unit->conversion_factor,
                        'sell_price' => $this->sell_price_per_unit * $unit->conversion_factor,
                    ]);
                }
            }
        }
    }

    // Get stock by location and unit
    public function getStockByLocation($warehouseId, $branchId, $unitId = null)
    {
        $query = $this->productStocks()
            ->where('warehouse_id', $warehouseId)
            ->where('branch_id', $branchId);

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        return $query->sum('quantity');
    }

    // Get total stock across all locations for a specific unit
    public function getTotalStock($unitId = null)
    {
        $query = $this->productStocks();

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        return $query->sum('quantity');
    }

    // Get stock movements for a specific date
    public function getStockMovementsForDate($date, $warehouseId = null, $branchId = null)
    {
        $query = $this->stockMovements()
            ->whereDate('created_at', $date);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    // Get daily stock summary
    public function getDailyStockSummary($date, $warehouseId = null, $branchId = null)
    {
        $movements = $this->getStockMovementsForDate($date, $warehouseId, $branchId);

        $summary = [
            'date' => $date,
            'opening_balance' => 0,
            'incoming' => 0,
            'outgoing' => 0,
            'closing_balance' => 0,
            'movements_count' => $movements->count(),
        ];

        foreach ($movements as $movement) {
            if ($movement->movement_type === 'in') {
                $summary['incoming'] += $movement->quantity;
            } else {
                $summary['outgoing'] += $movement->quantity;
            }
        }

        // Calculate closing balance (this would need to be calculated from previous day's closing + movements)
        $summary['closing_balance'] = $summary['incoming'] - $summary['outgoing'];

        return $summary;
    }

    // Get price history for a specific unit
    public function getPriceHistoryForUnit($unitId, $startDate = null, $endDate = null)
    {
        $query = $this->priceHistories()->where('unit_id', $unitId);

        if ($startDate) {
            $query->where('changed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('changed_at', '<=', $endDate);
        }

        return $query->get();
    }

    // Get latest price for a specific unit
    public function getLatestPriceForUnit($unitId)
    {
        return $this->priceHistories()
            ->where('unit_id', $unitId)
            ->latest('changed_at')
            ->first();
    }

    // Update product prices and record history
    public function updatePrices($newPurchasePrice, $newSellPrice, $reason = 'manual_update', $unitId = null, $sourceType = null, $sourceId = null, $sourceReference = null, $notes = null)
    {
        $oldPurchasePrice = $this->purchase_price_per_unit;
        $oldSellPrice = $this->sell_price_per_unit;

        // Update product prices
        $this->update([
            'purchase_price_per_unit' => $newPurchasePrice,
            'sell_price_per_unit' => $newSellPrice,
        ]);

        // Record price change history
        $this->recordPriceChange(
            $oldPurchasePrice,
            $newPurchasePrice,
            $oldSellPrice,
            $newSellPrice,
            $reason,
            $notes,
            $unitId,
            $sourceType,
            $sourceId,
            $sourceReference
        );

        // Update unit prices based on new base prices
        $this->updateUnitPricesFromBase();
    }

    // Scope to filter by parent category
    public function scopeByParentCategory($query, $parentCategoryId)
    {
        return $query->whereHas('category', function ($q) use ($parentCategoryId) {
            $q->where('id', $parentCategoryId);
        });
    }

    // Scope to filter by subcategory
    public function scopeBySubcategory($query, $subcategoryId)
    {
        return $query->where('subcategory_id', $subcategoryId);
    }

    // Scope to get low stock products
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= low_stock_threshold')
            ->where('stock', '>', 0);
    }

    // Scope to get out of stock products
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    // Scope to get products with custom low stock threshold
    public function scopeWithCustomLowStock($query, $threshold)
    {
        return $query->where('stock', '<=', $threshold)
            ->where('stock', '>', 0);
    }

    // Scope to get products with units
    public function scopeWithUnits($query)
    {
        return $query->whereHas('productUnits');
    }

    // Scope to get products with price history
    public function scopeWithPriceHistory($query)
    {
        return $query->whereHas('priceHistories');
    }

    // Scope to get products with stock movements
    public function scopeWithStockMovements($query)
    {
        return $query->whereHas('stockMovements');
    }

    // Computed attributes for table display
    public function getProfitMarginAttribute()
    {
        if ($this->purchase_price_per_unit > 0 && $this->sell_price_per_unit > 0) {
            return (($this->sell_price_per_unit - $this->purchase_price_per_unit) / $this->purchase_price_per_unit) * 100;
        }
        return null;
    }

    public function getProfitMarginColorAttribute()
    {
        if ($this->purchase_price_per_unit > 0 && $this->sell_price_per_unit > 0) {
            return ($this->sell_price_per_unit - $this->purchase_price_per_unit) > 0 ? 'success' : 'danger';
        }
        return 'gray';
    }

    public function getUnitsCountAttribute()
    {
        return $this->productUnits()->count();
    }
}
