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
        Schema::table('categories', function (Blueprint $table) {
            // إضافة الحقل الجديد لربط الفئات بـ ZDDK API
            $table->integer('zddk_category_id')->nullable()->after('name');
            

            // إضافة فهرس لتحسين الأداء إذا كنت تتوقع البحث المتكرر باستخدامها
            $table->index('zddk_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // حذف الفهرس أولاً
            $table->dropIndex(['zddk_category_id']);

            // حذف العمود في حالة التراجع عن الـ Migration
            $table->dropColumn('zddk_category_id');
        });
    }
};