<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    //
     protected $fillable = [
        'email',
        'password',
        'subscription_id'
    ];


    // Account.php
public function subscription()
{
    return $this->belongsTo(Subscription::class);
}

}
