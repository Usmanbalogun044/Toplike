<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'bio',
        'avatar_url',
        'phone_number',
        'country',
        'state',
        'lga',
        'is_verified',
        'verified_expires_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
