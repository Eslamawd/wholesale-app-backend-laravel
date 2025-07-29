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
        Schema::create('all_subs', function (Blueprint $table) {
            $table->id();

            $table->decimal('total', 12, 4)->default(0); // السعر الإجمالي للاشتراك
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            
            $table->unsignedBigInteger('user_seal_id')->nullable();
            $table->foreign('user_seal_id')->references('id')->on('user_seals');
            
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('all_subs');
    }
};
