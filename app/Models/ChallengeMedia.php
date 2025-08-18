<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeMedia extends Model
{
    protected $fillable = ['challenge_id', 'image_path'];

    // Define the relationship with the Challenge model
    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }
}
