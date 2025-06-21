<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'from_location_type',
        'from_location_id',
        'to_location_type',
        'to_location_id',
        'transfer_date',
        'status',
        'user_id',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromLocation()
    {
        return $this->morphTo('from_location');
    }

    public function toLocation()
    {
        return $this->morphTo('to_location');
    }
}
