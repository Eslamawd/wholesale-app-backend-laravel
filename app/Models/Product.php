<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'price' => 'float',
        'quantity' => 'integer',
    ];

    /**
     * العلاقة مع التصنيف (الفئة).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
