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
        Schema::table('orders', function (Blueprint $table) {
            // إضافة الحقول الجديدة
            $table->string('zddk_order_id')->nullable()->after('payment_method');
            $table->uuid('zddk_order_uuid')->nullable()->after('zddk_order_id'); // استخدم uuid إذا كان الحقل في DB هو UUID
            $table->string('zddk_status')->nullable()->after('zddk_order_uuid'); // 'wait', 'OK', 'CANCELLED'
            $table->json('zddk_delivery_data')->nullable()->after('zddk_status'); // لتخزين بيانات التسليم

            // إضافة فهرس (Index) لـ zddk_order_id لتحسين البحث إذا كنت ستستخدمه بشكل متكرر
            $table->index('zddk_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    
    {
        Schema::table('orders', function (Blueprint $table) {
            // حذف الفهرس أولاً
            $table->dropIndex(['zddk_order_id']);

            // حذف الأعمدة في حالة التراجع عن الـ Migration
            $table->dropColumn([
                'zddk_order_id',
                'zddk_order_uuid',
                'zddk_status',
                'zddk_delivery_data',
            ]);
        });
    }
};