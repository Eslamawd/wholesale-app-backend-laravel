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
        'price_wholesale',
        'quantity',
        'description',
        'manage_stock',
        'subscription',
        'user_fields',
        'show',
    ];

    
    protected $appends = [
        'old_price'
     ];

    protected $casts = [
        'user_fields' => 'array',
        'manage_stock' => 'boolean',
        'subscription' => 'boolean',
        'price' => 'float',
        'price_wholesale' => 'float',
        'quantity' => 'integer',
        'show' => 'boolean',
    ];

    /**
     * العلاقة مع التصنيف (الفئة).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope لتصفية المنتجات الظاهرة فقط.
     */
    // app/Models/Product.php

public function scopeVisible($query)
{
    return $query->where('show', true);
}


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


     public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'subscriptions')
                    ->withPivot(['duration', 'status', 'starts_at', 'ends_at'])
                    ->withTimestamps();
    }

    public function getOldPriceAttribute()
{
    $discount = Discount::first();
    $user = auth()->user();
    $spent = $user ? $user->allTotal() : 0;

    if (! $discount || ! $user) {
        return $this->price; // نفس السعر لو مفيش خصم
    }

    if ($user->role === 'seals' && $spent > $discount->seals_spend_threshold) {
        return $this->attributes['price'] ;
    }

    if ($spent > $discount->user_spend_threshold) {
        return $this->attributes['price_wholesale'];
    }

    return $this->price;
}


    private function applyDiscount($basePrice, $percentage)
{
    $discountAmount = $basePrice * ($percentage / 100);
    return round($basePrice - $discountAmount, 2);
}


public function getPriceAttribute($value)
{
    $discount = Discount::first();
    $spent = auth()->check() ? auth()->user()->allTotal() : 0;

    if ($discount && $spent > $discount->user_spend_threshold) {
        return $this->applyDiscount($this->attributes['price'], $discount->price_percentage_user);
    }

    return $this->attributes['price'];
}

public function getPriceWholesaleAttribute($value)
{
    $discount = Discount::first();
    $user = auth()->user();
    $spent = $user ? $user->allTotal() : 0;

    if ($discount && $user && $user->role === 'seals' && $spent > $discount->seals_spend_threshold) {
        return $this->applyDiscount($this->attributes['price_wholesale'], $discount->price_percentage_seals);
    }

    return $this->attributes['price_wholesale'];
}
// في الموديل Product مثلاً





}
