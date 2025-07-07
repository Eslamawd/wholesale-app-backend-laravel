<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'external_id',
        'category_external_id',
        'category_id',
        'name_ar',
        'name_en',
        'image',
        'price',
        'quantity',
        'description',
        'manage_stock',
        'user_fields',
    ];

    protected $casts = [
        'user_fields' => 'array',
        'manage_stock' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
