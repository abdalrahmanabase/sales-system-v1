<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @see \App\Policies\ProviderSalePolicy
 */
class ProviderSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'phone',
        'phone2',
        'notes',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
