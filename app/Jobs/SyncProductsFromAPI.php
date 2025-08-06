<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Product;
use App\Services\ThreeBECardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncProductsFromAPI implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

     protected $percentage;
    protected $percentageSeals;
    public function __construct(array $data)
    {

        $this->percentage = $data['percentage'];
        $this->percentageSeals = $data['percentage_seals'];
        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $page = 1; 
        $limit = 50; // الحد الأقصى للصفحات


        $service = new ThreeBECardService();

        // تحميل التصنيفات وربطها حسب external_id
        $categories = Category::all()->keyBy('external_id');

        while ($page <= $limit) {
            $products = $service->getProducts($page); // جلب المنتجات حسب الصفحة
      

            if (empty($products)) {
                logger("No products found on page {$page}. Stopping...");
                break;
            }

            foreach ($products as $item) {
                $category = $categories[$item['category_id']] ?? null;

                 $basePrice = $item['price'] ?? 0;

                $product = Product::updateOrCreate(
                    ['external_id' => $item['id']],
                    [
                        'category_external_id' => $item['category_id'],
                        'category_id' => $category?->id,
                        'name_ar' => $item['name_ar'] ?? $item['name'],
                        'name_en' => $item['name'] ?? $item['name'],
                        'image' => $item['image'] ?? null,
                        'price' => $basePrice + ($basePrice * $this->percentage / 100),
                        'price_wholesale' => $basePrice + ($basePrice * $this->percentageSeals / 100),
                        'quantity' => $item['quantity'] ?? 0,
                        'description' => $item['descr'] ?? null,
                        'manage_stock' => $item['manage_stock'] ?? false,
                        'user_fields' => $item['user_fields'] ?? [],
                    ]
                );

           
            }
             logger("Page {$page} processed.");
            $page++;
        }

          logger("🎉 Sync completed.");
    }
}
