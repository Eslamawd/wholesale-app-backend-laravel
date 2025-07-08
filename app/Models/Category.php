<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = [
        'external_id',
        'name_ar',
        'name_en',
        'image',
        'parent_id',
    ];

    /**
     * العلاقة مع المنتجات.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * الفئة الأساسية (الأب).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * الفئات الفرعية (الأبناء).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function scopeParentsOnly($query)
{
    return $query->whereNull('parent_id');
}

}
