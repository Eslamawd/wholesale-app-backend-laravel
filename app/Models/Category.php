<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'external_id',
        'name_ar',
        'name_en',
        'image',
        'parent_id',
    ];

    // app/Models/Category.php

public function products()
{
    return $this->hasMany(Product::class);
}

    // لو عايز تجيب الأب (الفئة الأساسية)
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // لو عايز تجيب الأبناء (الفئات الفرعية)
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
