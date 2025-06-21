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
        'provider_id',
        'purchase_price_per_unit',
        'sell_price_per_unit',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'purchase_price_per_unit' => 'decimal:2',
        'sell_price_per_unit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
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
}
