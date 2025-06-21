<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_name',
        'phone',
        'address',
        'notes',
        'user_id',
        'branch_id',
        'order_status',
        'source',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(CallOrderItem::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
