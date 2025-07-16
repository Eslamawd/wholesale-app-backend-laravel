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
        Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('category_id')->nullable(); // لعلاقة belongsTo
        $table->string('external_id')->nullable(); // ID من API 3BE Card
        $table->string('category_external_id')->nullable(); // ID من API برضو

        $table->string('name_ar')->nullable();
        $table->string('name_en')->nullable();
        $table->string('image')->nullable();
        $table->decimal('price', 12, 4)->default(0);
        $table->decimal('price_wholesale', 12, 4)->default(0);
        $table->unsignedBigInteger('quantity')->default(0);
        $table->text('description')->nullable();
        $table->boolean('manage_stock')->default(false);
        $table->boolean('subscription')->default(false);
        $table->json('user_fields')->nullable(); // معلومات مثل ID المستخدم
        $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete(); // علاقة مع جدول الفئات
      
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
