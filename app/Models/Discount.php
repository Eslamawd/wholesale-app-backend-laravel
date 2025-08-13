<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    //
    protected $fillable = [
        
        'price_percentage_user',
        'price_percentage_seals',
        'user_spend_threshold',
        'seals_spend_threshold',
    ];
}
