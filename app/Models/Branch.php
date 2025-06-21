<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'address',
        'manager_name',
    ];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function revenues()
    {
        return $this->hasMany(Revenue::class);
    }

    public function cashierShifts()
    {
        return $this->hasMany(CashierShift::class);
    }

    public function callOrders()
    {
        return $this->hasMany(CallOrder::class);
    }

    public function profitReports()
    {
        return $this->hasMany(ProfitReport::class);
    }
}
