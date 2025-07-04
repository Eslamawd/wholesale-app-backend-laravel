<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_price',
        'payment_status',
        'payment_method',
        'used_balance',
        // إضافة الحقول الجديدة لتتبع حالة طلبات ZDDK API
        'zddk_order_id',        // String/Nullable: معرف الطلب الذي يرجعه ZDDK API
        'zddk_order_uuid',      // String/Nullable: UUID الذي أرسلناه إلى ZDDK API
        'zddk_status',          // String/Nullable: حالة الطلب من ZDDK API ('wait', 'OK', 'CANCELLED')
        'zddk_delivery_data',   // JSON/Nullable: أي بيانات تسليم إضافية من ZDDK (مثل كود الشحن، بيانات الحساب)
    ];

    protected $table = 'orders';

    // إضافة casts للحقول JSON لسهولة التعامل معها كـ Arrays/Objects
    protected $casts = [
        'zddk_delivery_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'order_service')
                    ->withPivot('quantity', 'price');
    }
}