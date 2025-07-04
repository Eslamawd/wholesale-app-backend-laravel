<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'zddk_category_id', 
        'image_path', // Assuming you added this field to the categories table
    ];

    protected $table = 'categories';

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}