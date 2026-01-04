<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = ['user_id', 'amount', 'type', 'reference', 'status', 'description'];
    protected $casts = [
        'amount' => 'float',
        'status' => 'boolean',
    ];
    protected $attributes = [
        'status' => false,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
