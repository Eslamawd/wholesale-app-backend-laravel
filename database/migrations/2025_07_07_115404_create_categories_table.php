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
        Schema::create('categories', function (Blueprint $table) {
             $table->id();
    $table->string('external_id')->unique(); // ID من API
    $table->string('name_ar');
    $table->string('name_en');
    $table->string('image')->nullable();
    $table->unsignedBigInteger('parent_id')->nullable(); // علاقة أبو-ابن
    
    $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
