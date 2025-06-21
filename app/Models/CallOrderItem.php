<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_order_id',
        'product_id',
        'quantity',
        'price_per_unit',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function callOrder()
    {
        return $this->belongsTo(CallOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
