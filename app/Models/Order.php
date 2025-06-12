<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
     protected $fillable = [
        'user_id',
        'total_price',
        'payment_status',
        'payment_method',
        'used_balance',
    ];
    protected $table = 'orders';
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function services()
    {
        return $this->belongsToMany(Service::class, 'order_service')
                    ->withPivot('quantity', 'price');
    }
 

}
