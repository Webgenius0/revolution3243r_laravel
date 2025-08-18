<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'challenge_type',
        'start_date',
        'end_date',
        'start_location',
        'start_latitude',
        'start_longitude',
        'end_location',
        'end_latitude',
        'end_longitude',
        'description'
    ];

    // 'start_date', 'end_date', casting to DateTime
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'start_latitude' => 'decimal:7',
        'start_longitude' => 'decimal:7',
        'end_latitude' => 'decimal:7',
        'end_longitude' => 'decimal:7',
    ];

    //relation with User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define relationships with ChallengeRequirement and ChallengeMedia models
    public function requirements()
    {
        return $this->hasMany(ChallengeRequirement::class);
    }

    public function media()
    {
        return $this->hasMany(ChallengeMedia::class);
    }

    //Relation with ChallengeParticipant model
    public function participants()
    {
        return $this->hasMany(ChallengeParticipant::class);
    }
}
