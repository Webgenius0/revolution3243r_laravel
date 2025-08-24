<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',

        // ✅ Start Location
        'start_name',
        'start_lat',
        'start_lng',

        // ✅ End Location
        'end_name',
        'end_lat',
        'end_lng',

        'description',
    ];

    // One Track → Many Images
    public function images()
    {
        return $this->hasMany(TrackImage::class);
    }


}
