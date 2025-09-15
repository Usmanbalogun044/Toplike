<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'reference',
        'description',
    ];

    protected $casts = [
        'amount' => 'float',
        'status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(UserWallet::class, 'user_id', 'user_id');
    }

    public function bankaccount()
    {
        return $this->belongsTo(BankAccount::class, 'user_id', 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'user_id', 'user_id');
    }
}
