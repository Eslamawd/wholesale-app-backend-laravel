<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSeals extends Model
{
    //
    protected $fillable = [
        'phone',
        'name',
        'user_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function allSubs(): HasMany
    {
        return $this->hasMany(AllSub::class, 'user_seal_id');
    }
}
