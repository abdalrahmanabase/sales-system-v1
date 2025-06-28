<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'branch_id',
        'unit_id',
        'movement_type',
        'quantity',
        'source_type',
        'source_id',
        'source_reference',
        'product_stock_id',
        'quantity_before',
        'quantity_after',
        'changed_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_before' => 'decimal:4',
        'quantity_after' => 'decimal:4',
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

    public function productStock()
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // Helper method to record stock movement
    public static function recordMovement($productStock, $movementType, $quantity, $notes = null, $sourceType = null, $sourceId = null, $sourceReference = null)
    {
        $quantityBefore = $productStock->quantity;
        $quantityAfter = $quantityBefore + $quantity;

        return static::create([
            'product_id' => $productStock->product_id,
            'warehouse_id' => $productStock->warehouse_id,
            'branch_id' => $productStock->branch_id,
            'unit_id' => $productStock->unit_id,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_reference' => $sourceReference,
            'product_stock_id' => $productStock->id,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'changed_by' => auth()->id(),
            'notes' => $notes,
        ]);
    }
}
