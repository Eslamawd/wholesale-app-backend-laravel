<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPassword;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;


class User extends Authenticatable implements Wallet, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasWallet;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'balance',
        'role',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */

    public function sendEmailVerificationNotification()
{
    $this->notify(new CustomVerifyEmail());
}





public function sendPasswordResetNotification($token)
{
    $this->notify(new CustomResetPassword($token));
}


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
    
       public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
       public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id');
    }
   


    public function subscriptions()
{
    return $this->hasMany(Subscription::class);
}

public function product()
{
    return $this->belongsToMany(Product::class, 'subscriptions')
                ->withPivot(['duration', 'status', 'starts_at', 'ends_at'])
                ->withTimestamps();
}

    public function userSeals()
    {
        return $this->hasMany(UserSeals::class, 'user_id');
    }

   
}
