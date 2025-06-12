<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    //
    protected $fillable = ['title', 'description', 'price', 'category_id', 'image_path'];
    protected $table = 'services';
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_service')
                    ->withPivot('quantity', 'price');
    }
    public function getPriceAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = number_format($value, 2, '.', '');
    }
    public function getImagePathAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
}
