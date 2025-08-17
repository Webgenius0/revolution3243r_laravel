<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Following extends Model
{
    //

    protected $table = 'follows';

    protected $fillable = [
        'follower_id',
        'following_id',
    ];

    // If you want to disable timestamps (if you don’t use them)
    // public $timestamps = false;

    // Define the user who is following
    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    // Define the user who is being followed
    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}
