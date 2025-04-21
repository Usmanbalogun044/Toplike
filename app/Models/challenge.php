<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class challenge extends Model
{
    protected $fillable = ['week_number', 'year', 'entry_fee', 'total_pool', 'starts_at', 'ends_at','is_completed'];
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_completed' => 'boolean',
    ];
    protected $attributes = [
        'is_completed' => false,
    ];

    public function entries()
    {
        return $this->hasMany(ChallengeEntry::class);
    }
}
