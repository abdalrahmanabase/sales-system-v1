<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'label',
        'city',
        'area',
        'street',
        'building',
        'floor',
        'apartment',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getFullAddressAttribute()
    {
        return "{$this->street}, {$this->building}, {$this->area}, {$this->city}";
    }
}
