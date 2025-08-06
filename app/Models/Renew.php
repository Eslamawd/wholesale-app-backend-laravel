<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Renew extends Model
{
    //

    protected $fillable = [   
        'user_id' ,
        'duration' ,
        'subscription_id',
      
    ];


     public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

     public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
