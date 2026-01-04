<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
     protected $fillable = [
        'user_id',
        'account_number',
        'bank_name',
        'bank_code',
        'account_name',
        'account_type',
        'is_verified',
        'recipient_code'
    ];

    protected $attributes = [
        'is_verified' => false,
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function wallet()
    {
        return $this->hasOne(UserWallet::class);
    }
}
