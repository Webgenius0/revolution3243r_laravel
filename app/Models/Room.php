<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['user_one_id', 'user_two_id', 'notifications'];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }


    public function users()
{
    return $this->belongsToMany(User::class, 'room_user')
                ->withPivot('notifications') // include the extra column
                ->withTimestamps();          // include created_at & updated_at
}
}
