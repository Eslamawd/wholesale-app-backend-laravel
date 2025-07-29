<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllSub extends Model
{
    //
    protected $fillable = [
        'subscription_id',
        'user_seal_id',
        'total',
        'order_id',
    ];
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
    public function userSeal()
    {
        return $this->belongsTo(UserSeals::class, 'user_seal_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}
