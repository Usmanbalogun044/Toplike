<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Challenge extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'week_number',
        'year',
        'status',
        'starts_at',
        'ends_at',
        'entry_fee',
        'prize_pool',
        'rules',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'entry_fee' => 'decimal:4',
        'prize_pool' => 'decimal:4',
    ];

    public function entries()
    {
        return $this->hasMany(ChallengeEntry::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
