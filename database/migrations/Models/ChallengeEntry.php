<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeEntry extends Model
{
    protected $fillable = ['challenge_id', 'user_id', 'has_posted', 'has_paid'];
    protected $casts = [
        'has_posted' => 'boolean',
        'is_winner' => 'boolean',
        'is_visible' => 'boolean',
        'is_approved' => 'boolean',
        'is_rejected' => 'boolean',
        'is_winner_paid' => 'boolean',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
