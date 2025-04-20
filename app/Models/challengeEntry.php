<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class challengeEntry extends Model
{
    protected $fillable = ['challenge_id', 'user_id', 'has_posted'];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
