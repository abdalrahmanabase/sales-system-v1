<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_stock_id',
        'user_id',
        'quantity_change',
        'reason',
        'notes',
    ];

    public function productStock()
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
