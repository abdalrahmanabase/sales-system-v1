<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'branch_id',
        'quantity',
        'unit_id',
        'last_updated_at',
        'source_type',
        'source_id',
        'source_reference',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'last_updated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function inventoryAdjustments()
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // Get stock movements for a specific date
    public function getStockMovementsForDate($date)
    {
        return $this->stockMovements()
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Get stock movements for a date range
    public function getStockMovementsForDateRange($startDate, $endDate)
    {
        return $this->stockMovements()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Get daily stock summary
    public function getDailyStockSummary($date)
    {
        $movements = $this->getStockMovementsForDate($date);
        
        $summary = [
            'date' => $date,
            'opening_stock' => 0,
            'closing_stock' => $this->quantity,
            'incoming' => 0,
            'outgoing' => 0,
            'adjustments' => 0,
            'movements' => $movements,
        ];

        foreach ($movements as $movement) {
            switch ($movement->movement_type) {
                case 'incoming':
                    $summary['incoming'] += $movement->quantity;
                    break;
                case 'outgoing':
                    $summary['outgoing'] += abs($movement->quantity);
                    break;
                case 'adjustment':
                    $summary['adjustments'] += $movement->quantity;
                    break;
            }
        }

        // Calculate opening stock
        $summary['opening_stock'] = $summary['closing_stock'] - $summary['incoming'] + $summary['outgoing'] - $summary['adjustments'];

        return $summary;
    }

    // Get stock movements by source type
    public function getStockMovementsBySource($sourceType, $sourceId = null)
    {
        $query = $this->stockMovements()->where('source_type', $sourceType);
        
        if ($sourceId) {
            $query->where('source_id', $sourceId);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    // Get stock movements from provider
    public function getProviderStockMovements($providerId = null)
    {
        return $this->getStockMovementsBySource('provider', $providerId);
    }

    // Get stock movements from sales
    public function getSalesStockMovements($saleId = null)
    {
        return $this->getStockMovementsBySource('sale', $saleId);
    }

    // Get stock movements from returns
    public function getReturnStockMovements($returnId = null)
    {
        return $this->getStockMovementsBySource('return', $returnId);
    }

    // Get stock movements from transfers
    public function getTransferStockMovements($transferId = null)
    {
        return $this->getStockMovementsBySource('transfer', $transferId);
    }

    // Get stock movements from adjustments
    public function getAdjustmentStockMovements($adjustmentId = null)
    {
        return $this->getStockMovementsBySource('adjustment', $adjustmentId);
    }

    // Scope to get stocks by warehouse
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // Scope to get stocks by branch
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Scope to get stocks with positive quantity
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    // Scope to get low stock items
    public function scopeLowStock($query, $threshold = null)
    {
        if ($threshold) {
            return $query->where('quantity', '<=', $threshold);
        }
        
        return $query->whereRaw('quantity <= products.low_stock_threshold')
            ->join('products', 'product_stocks.product_id', '=', 'products.id');
    }

    // Scope to get out of stock items
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    // Record stock movement when quantity changes
    public function recordStockMovement($movementType, $quantity, $notes = null, $sourceType = null, $sourceId = null, $sourceReference = null)
    {
        return StockMovement::recordMovement(
            $this,
            $movementType,
            $quantity,
            $notes,
            $sourceType,
            $sourceId,
            $sourceReference
        );
    }

    // Update quantity and record movement
    public function updateQuantity($newQuantity, $movementType = 'adjustment', $notes = null, $sourceType = null, $sourceId = null, $sourceReference = null)
    {
        $oldQuantity = $this->quantity;
        $quantityChange = $newQuantity - $oldQuantity;

        // Update the stock quantity and source fields
        $updateData = [
            'quantity' => $newQuantity,
            'last_updated_at' => now(),
        ];
        if ($sourceType !== null) {
            $updateData['source_type'] = $sourceType;
        }
        if ($sourceId !== null) {
            $updateData['source_id'] = $sourceId;
        }
        if ($sourceReference !== null) {
            $updateData['source_reference'] = $sourceReference;
        }
        $this->update($updateData);

        // Record the movement if there was a change
        if ($quantityChange != 0) {
            $this->recordStockMovement(
                $movementType,
                $quantityChange,
                $notes,
                $sourceType,
                $sourceId,
                $sourceReference
            );
        }

        return $this;
    }

    // Add stock (incoming)
    public function addStock($quantity, $notes = null, $sourceType = null, $sourceId = null, $sourceReference = null)
    {
        return $this->updateQuantity(
            $this->quantity + $quantity,
            'in',
            $notes,
            $sourceType,
            $sourceId,
            $sourceReference
        );
    }

    // Remove stock (outgoing)
    public function removeStock($quantity, $notes = null, $sourceType = null, $sourceId = null, $sourceReference = null)
    {
        return $this->updateQuantity(
            $this->quantity - $quantity,
            'out',
            $notes,
            $sourceType,
            $sourceId,
            $sourceReference
        );
    }

    // Adjust stock (manual adjustment)
    public function adjustStock($newQuantity, $notes = null, $sourceType = null, $sourceId = null, $sourceReference = null)
    {
        return $this->updateQuantity(
            $newQuantity,
            'adjustment',
            $notes,
            $sourceType,
            $sourceId,
            $sourceReference
        );
    }

    // Transfer stock to another location
    public function transferStock($quantity, $targetWarehouseId, $targetBranchId, $notes = null, $sourceType = null, $sourceId = null, $sourceReference = null)
    {
        // Remove from current location
        $this->removeStock($quantity, $notes, $sourceType, $sourceId, $sourceReference);

        // Add to target location
        $targetStock = static::firstOrCreate([
            'product_id' => $this->product_id,
            'warehouse_id' => $targetWarehouseId,
            'branch_id' => $targetBranchId,
            'unit_id' => $this->unit_id,
        ], [
            'quantity' => 0,
            'last_updated_at' => now(),
        ]);

        $targetStock->addStock($quantity, $notes, $sourceType, $sourceId, $sourceReference);

        return $this;
    }
}
