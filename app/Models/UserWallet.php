<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserWallet extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'withdrawal_pin',
        'is_frozen',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'is_frozen' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'wallet_id');
    }
}
