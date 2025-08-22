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

    private $discountCache = null;

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
    public function scopeVisible($query)
    {
        return $query->where('show', true);
    }

    /**
     * صورة المنتج.
     */
    public function getImageAttribute($value)
    {
        if (!$value) return null;

        if (str_starts_with($value, 'http')) {
            return $value;
        }

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

    /**
     * ✅ كاش داخلي للخصومات (عشان نقلل الاستعلامات).
     */
    private function getDiscount()
    {
        if ($this->discountCache === null) {
            $this->discountCache = Discount::where('category_id', $this->category_id)->first();
        }
        return $this->discountCache;
    }

    /**
     * السعر القديم (قبل الخصم).
     */
    public function getOldPriceAttribute()
    {
        return $this->attributes['price'];
    }

    /**
     * السعر الحالي بعد الخصم (للمستخدم العادي).
     */
    public function getPriceAttribute($value)
    {
        $discount = $this->getDiscount();
        $spent = auth()->check() ? auth()->user()->allTotal() : 0;

        if ($discount && $spent > $discount->user_spend_threshold) {
            return $this->applyDiscount($this->attributes['price'], $discount->price_percentage_user);
        }

        return $this->attributes['price'];
    }

    /**
     * سعر الجملة بعد الخصم (لـ seals).
     */
    public function getPriceWholesaleAttribute($value)
    {
        $discount = $this->getDiscount();
        $user = auth()->user();
        $spent = $user ? $user->allTotal() : 0;

        if ($discount && $user && $user->role === 'seals' && $spent > $discount->seals_spend_threshold) {
            return $this->applyDiscount($this->attributes['price_wholesale'], $discount->price_percentage_seals);
        }

        return $this->attributes['price_wholesale'];
    }

    /**
     * دالة لتطبيق الخصم.
     */
    private function applyDiscount($basePrice, $percentage)
    {
        $discountAmount = $basePrice * ($percentage / 100);
        return round($basePrice - $discountAmount, 2);
    }
}
