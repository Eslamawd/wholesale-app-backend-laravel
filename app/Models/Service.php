<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Add this line if not already present

class Service extends Model
{
    use HasFactory; // Use HasFactory trait if you plan to use factories

    protected $fillable = [
        'title',
        'description',
        'price',
        'category_id',
        'image_path',
        'is_zddk_product',
        'zddk_product_id',
        'product_type',
        'zddk_required_params',
        'zddk_qty_values',
    ];

    protected $table = 'services';

    protected $casts = [
        'is_zddk_product' => 'boolean',
        'zddk_required_params' => 'array',
        'zddk_qty_values' => 'array',
    ];

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

    // --- START: MODIFIED getImagePathAttribute ---
    public function getImagePathAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Check if the path is already a full URL (http or https)
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value; // It's already a full URL, return as is
        }

        // If it's not a full URL, assume it's a local storage path
        return asset('storage/' . $value);
    }
    // --- END: MODIFIED getImagePathAttribute ---
}