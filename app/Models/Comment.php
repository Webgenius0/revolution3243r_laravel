<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    //


    public function replies()
{
    return $this->hasMany(Comment::class, 'parent_id');
}
    public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}
}
