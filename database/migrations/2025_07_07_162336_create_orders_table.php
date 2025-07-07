<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
             $table->id();
             
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('product_id');
    $table->integer('count');
    $table->decimal('total_price', 12, 4)->default(0); // السعر الإجمالي للأوردر
    $table->json('user_fields');
    $table->string('external_order_id')->nullable(); // ID من 3BE لو رجعوه
    $table->json('response')->nullable(); // نخزن رد الـ API
    $table->timestamps();

    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
