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
        Schema::table('services', function (Blueprint $table) {
            // إضافة الحقول الجديدة
            $table->boolean('is_zddk_product')->default(false)->after('image_path');
            $table->integer('zddk_product_id')->nullable()->after('is_zddk_product');
            $table->string('product_type')->nullable()->after('zddk_product_id'); // 'package', 'amount', 'specificPackage'
            $table->json('zddk_required_params')->nullable()->after('product_type'); // لتخزين ['playerId', 'phone']
            $table->json('zddk_qty_values')->nullable()->after('zddk_required_params'); // لتخزين [10, 20, 50] لكميات الشحن

            // إضافة فهارس (Indexes) لتحسين الأداء إذا كنت تتوقع البحث المتكرر باستخدامها
            $table->index('is_zddk_product');
            $table->index('zddk_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // حذف الفهارس أولاً
            $table->dropIndex(['is_zddk_product']);
            $table->dropIndex(['zddk_product_id']);

            // حذف الأعمدة في حالة التراجع عن الـ Migration
            $table->dropColumn([
                'is_zddk_product',
                'zddk_product_id',
                'product_type',
                'zddk_required_params',
                'zddk_qty_values',
            ]);
        });
    }
};