<?php
// app/Models/Order.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'count',
        'total_price',
        'user_fields',
        'external_order_id',
        'response',
    ];

    protected $casts = [
        'user_fields' => 'array',
        'response' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
