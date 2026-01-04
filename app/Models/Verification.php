<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    use HasUuids;

    protected $fillable = [
        'identifier',
        'code',
        'type',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function scopeValid($query, $identifier, $code, $type)
    {
        return $query->where('identifier', $identifier)
                     ->where('code', $code)
                     ->where('type', $type)
                     ->where('expires_at', '>', now())
                     ->whereNull('verified_at');
    }
}
