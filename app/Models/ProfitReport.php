<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_period',
        'branch_id',
        'total_sales',
        'total_cost',
        'total_expenses',
        'net_profit',
    ];

    protected $casts = [
        'total_sales' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_profit' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
