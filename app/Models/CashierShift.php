<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'start_time',
        'end_time',
        'starting_cash_amount',
        'ending_cash_amount',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time',
        'starting_cash_amount' => 'decimal:2',
        'ending_cash_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
