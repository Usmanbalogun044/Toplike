<?php

namespace App\Models;

 use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        'name', 'username', 'email', 'profile_picture', 
        'bio', 'last_login', 'is_active', 'is_suspended',
         'password','email_verification_token','email_verified_at',
         'image_public_id'
    ];

    // You might want to cast some fields
    protected $casts = [
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'is_suspended' => 'boolean',
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
    public function bankaccount()
    {
        return $this->hasOne(bankaccount::class);
    }
}
