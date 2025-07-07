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
        $service = new ThreeBECardService();

        $page = 1;
        $pageSize = 50;
        $imported = 0;

        do {
            $response = $service->getProducts($page, $pageSize);

            if (!isset($response['data']) || empty($response['data'])) {
                break;
            }

            $categories = Category::all()->keyBy('external_id');

            foreach ($response['data'] as $item) {
                $category = $categories[$item['category_id']] ?? null;

                Product::updateOrCreate(
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

                $imported++;
            }

            $page++;
        } while (!empty($response['data']));

        $this->command->info("✅ Products imported successfully: {$imported} items.");
    }
}
