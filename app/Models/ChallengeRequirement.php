<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeRequirement extends Model
{
    protected $fillable = ['challenge_id', 'requirement_type'];

    // Define the relationship with the Challenge model
    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }
}
