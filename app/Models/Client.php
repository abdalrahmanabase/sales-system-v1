<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'notes',
    ];

    public function phones()
    {
        return $this->hasMany(ClientPhone::class);
    }

    public function addresses()
    {
        return $this->hasMany(ClientAddress::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }
}
