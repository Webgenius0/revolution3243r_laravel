<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'track_id',
        'image',
    ];

    // Belongs to Track
    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
