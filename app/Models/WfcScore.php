<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WfcScore extends Model
{
    protected $fillable = [
        'cafe_id',
        'user_id',
        'wifi_rating',
        'outlet_rating',
        'comfort_rating',
        'is_verified',
        'user_lat',
        'user_lng',
        'comment',
    ];

    public function cafe()
    {
        return $this->belongsTo(Cafe::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
