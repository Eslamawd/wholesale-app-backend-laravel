<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPercentageCategories implements ShouldQueue
{
    use Queueable;

    protected $percentage;
    protected $percentageSeals;
    protected $category_id;

    public function __construct(array $data)
    {
        $this->percentage      = $data['percentage'];
        $this->percentageSeals = $data['percentage_seals'];
        $this->category_id     = $data['category_id'];
    }

    public function handle(): void
    {
        $products = Product::where('category_id', $this->category_id)->get();

        if ($products->isEmpty()) {
            logger("No products found for category {$this->category_id}. Stopping...");
            return;
        }

        foreach ($products as $item) {
            $price = $item->price ?? 0;
            $priceWholesale = $item->price_wholesale ?? $price;

            $item->update([
                'price'           => $price + ($price * $this->percentage / 100),
                'price_wholesale' => $priceWholesale + ($priceWholesale * $this->percentageSeals / 100),
            ]);
        }

        logger("Updated {$products->count()} products in category {$this->category_id}.");
    }
}
