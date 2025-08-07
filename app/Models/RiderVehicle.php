<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderVehicle extends Model
{
    protected $fillable = [
        'user_id',
        'engine_type',
        'engine_size',
        'tire_type',
        'model',
        'front_suspension',
        'rear_suspension',
        'front_sprocket',
        'rear_sprocket',
    ];
}
