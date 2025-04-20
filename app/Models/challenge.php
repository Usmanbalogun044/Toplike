<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class challenge extends Model
{
    protected $fillable = ['week_number', 'year', 'entry_fee', 'total_pool', 'starts_at', 'ends_at'];

    public function entries()
    {
        return $this->hasMany(ChallengeEntry::class);
    }
}
