<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\ThreeBECardService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $page = 1; 
        $limit = 50; // الحد الأقصى للصفحات


        $service = new ThreeBECardService();

        // تحميل التصنيفات وربطها حسب external_id
        $categories = Category::all()->keyBy('external_id');

        while ($page <= $limit) {
            $products = $service->getProducts($page); // جلب المنتجات حسب الصفحة
      

            if (empty($products)) {
                $this->command->warn("🚫 No products found on page {$page}. Stopping...");
                break;
            }

            foreach ($products as $item) {
                $category = $categories[$item['category_id']] ?? null;

                $product = Product::updateOrCreate(
                    ['external_id' => $item['id']],
                    [
                        'category_external_id' => $item['category_id'],
                        'category_id' => $category?->id,
                        'name_ar' => $item['name_ar'] ?? $item['name'],
                        'name_en' => $item['name_en'] ?? $item['name'],
                        'image' => $item['image'] ?? null,
                        'price' => $item['price'] ?? 0,
                        'quantity' => $item['quantity'] ?? 0,
                        'description' => $item['descr'] ?? null,
                        'manage_stock' => $item['manage_stock'] ?? false,
                        'user_fields' => $item['user_fields'] ?? [],
                    ]
                );

           
            }

            $this->command->info("✅ Page {$page} processed.");
            $page++;
        }

        $this->command->info("🎉 Done. Total imported products: .");
    }
}
