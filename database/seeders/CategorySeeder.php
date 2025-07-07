<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\ThreeBECardService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    
        public function run()
    {
        $api = new ThreeBECardService();
        $categories = $api->getCategories();

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['external_id' => $cat['id']],
                [
                    'name_ar' => $cat['name_ar'] ?? $cat['name'],
                    'name_en' => $cat['name_en'] ?? $cat['name'],
                    'image' => $cat['image'] ?? null,
                    'parent_id' => $cat['parent_id'] ?: null,
                ]
            );
        }

        echo "✔️ Categories imported successfully.\n";
    }
    
}
