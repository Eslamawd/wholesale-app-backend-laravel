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

    public function getImageAttribute($value)
{
    if (!$value) return null;

    // لو الصورة رابط خارجي زي 3becard.com رجّعها زي ما هي
    if (str_starts_with($value, 'http')) {
        return $value;
    }

    // لو الصورة محفوظة في storage/public/categories
    return asset('storage/' . $value);
}


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
