<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    //
    protected $guarded = [];

    public function getFilePathAttribute($value)
    {
        return $value ? url($value) : null;
    }
 }
