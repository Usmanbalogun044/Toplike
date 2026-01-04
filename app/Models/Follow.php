<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'follower_id',
        'following_id',
    ];
}
