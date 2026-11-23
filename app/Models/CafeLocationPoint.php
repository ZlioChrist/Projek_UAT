<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CafeLocationPoint extends Model
{
    protected $fillable = [
        'name',
        'address',
        'slug',
        'latitude',
        'longitude',
        'google_maps_url',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
