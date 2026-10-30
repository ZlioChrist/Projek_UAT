<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function cafes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Cafe::class);
    }
}
