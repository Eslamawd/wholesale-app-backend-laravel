<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    //
    protected $fillable = [
        
        'price_percentage_user',
        'price_percentage_seals',
        'category_id',
        'user_spend_threshold',
        'seals_spend_threshold',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    // في الموديل
public function getPricePercentageUserAttribute($value)
{
    return intval($value);
}
public function getUserSpendThresholdAttribute($value)
{
    return intval($value);
}

public function getPricePercentageSealsAttribute($value)
{
    return intval($value);
}

public function getSealsSpendThresholdAttribute($value)
{
    return intval($value);
}

}
