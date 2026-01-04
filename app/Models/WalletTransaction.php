<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id',
        'type',
        'direction',
        'amount',
        'reference',
        'description',
        'status',
        'meta_data',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'meta_data' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(UserWallet::class, 'wallet_id');
    }
}
