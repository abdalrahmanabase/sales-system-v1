<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name_id',
        'notes',
    ];

    public function companyName()
    {
        return $this->belongsTo(CompanyName::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(ProviderPayment::class);
    }
}
