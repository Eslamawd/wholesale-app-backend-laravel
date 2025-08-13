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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_percentage_user', 10, 2)->nullable(); 
            $table->decimal('price_percentage_seals', 10, 2)->nullable(); 
            $table->decimal('user_spend_threshold', 10, 2)->default(0); 
            $table->decimal('seals_spend_threshold', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
