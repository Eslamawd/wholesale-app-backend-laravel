<?php

namespace App\Jobs;

use App\Models\Category;
use App\Services\ThreeBECardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCategoriesFromAPI implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $page = 1;
        $limit = 50;
        $api = new ThreeBECardService();
        $allCategories = [];

        // المرحلة 1: جلب كل التصنيفات وتخزينهم بدون parent_id
        while ($page <= $limit) {
            $categories = $api->getCategories($page);

            if (empty($categories)) {
                break;
            }

            foreach ($categories as $cat) {
                $allCategories[] = $cat;

                Category::updateOrCreate(
                    ['external_id' => $cat['id']],
                    [
                        'name_ar' => $cat['name_ar'] ?? $cat['name'],
                        'name_en' => $cat['name'] ?? $cat['name'],
                        'image' => $cat['image'] ?? null,
                        // لا نضع parent_id الآن
                    ]
                );
            }

            echo "✅ Page $page imported.\n";
            $page++;
        }

        // المرحلة 2: تحديث parent_id بعد التأكد من وجود الأب
        foreach ($allCategories as $cat) {
            if (!empty($cat['parent_id'])) {
                $parent = Category::where('external_id', $cat['parent_id'])->first();
                $child = Category::where('external_id', $cat['id'])->first();

                if ($parent && $child) {
                    $child->parent_id = $parent->id;
                    $child->save();
                }
            }
        }

        echo "✅ Categories imported and parents updated successfully.\n";
    
    }
}
