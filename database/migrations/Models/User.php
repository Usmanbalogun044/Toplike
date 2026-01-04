<?php

namespace App\Models;

 use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\BankAccount;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phonenumber',
        'country',
        'state',
        'lga',
        'role',
        'referal_code',
        'balance',
        'withdrawal_pin',
        'device_token',
        'image_public_id',
        'is_verified',
        'verified_expires_at',
        'profile_picture',
        'bio',
        'last_login',
        'is_active',
        'is_suspended',
        'email_verification_token',
        'email_verified_at',
    ];

    // You might want to cast some fields
    protected $casts = [
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'is_suspended' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'verified_expires_at' => 'datetime',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function isSuspended()
    {
        return $this->is_suspended;
    }

    public function suspendUser()
    {
        $this->is_suspended = true;
        $this->save();
    }

    public function unsuspendUser()
    {
        $this->is_suspended = false;
        $this->save();
    }
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
    public function wallet()
    {
        return $this->hasOne(UserWallet::class);
    }
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
    public function createwallet()
    {
        return $this->hasOne(UserWallet::class)->create([
            'user_id' => $this->id,
            'balance' => 0,
            'last_transaction_at' => now(),
        ]);
    }
    public function challenge()
    {
        return $this->hasMany(Challenge::class);
    }
    //challengeentry
    public function challengeentry()
    {
        return $this->hasMany(ChallengeEntry::class);
    }
    public function bank()
    {
        return $this->hasOne(Bank::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)->latestOfMany()->where('status', 'active')->where('expires_at', '>', now());
    }
}
