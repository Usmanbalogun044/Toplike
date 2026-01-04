<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'ip_address',
        'user_agent',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
