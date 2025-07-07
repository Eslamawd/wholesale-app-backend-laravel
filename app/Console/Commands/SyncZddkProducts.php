<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZddkApiService;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class SyncZddkProducts extends Command
{
    protected $signature = 'sync:zddk-data';
    protected $description = 'Synchronize products and categories from ZDDK API to local database.';

    protected $zddkApi;

    public function __construct(ZddkApiService $zddkApi)
    {
        parent::__construct();
        $this->zddkApi = $zddkApi;
    }

    public function handle()
    {
        $this->info('Starting ZDDK products and categories synchronization...');

        // 1. مزامنة الفئات (Categories) - يجب أن تتم أولاً
        $this->syncCategories();

        // 2. مزامنة المنتجات (Products)
        $this->syncProducts();

        $this->info('ZDDK synchronization completed.');
        return Command::SUCCESS;
    }

    protected function syncCategories()
    {
        $this->info('Syncing ZDDK categories...');
        $zddkCategoriesResponse = $this->zddkApi->getCategories();

        $categoriesToProcess = [];
        if (isset($zddkCategoriesResponse['categories']) && is_array($zddkCategoriesResponse['categories'])) {
            $categoriesToProcess = $zddkCategoriesResponse['categories'];
        } elseif (is_array($zddkCategoriesResponse) && !empty($zddkCategoriesResponse)) {
            if (!isset($zddkCategoriesResponse['status']) && !isset($zddkCategoriesResponse['products'])) {
                $categoriesToProcess = $zddkCategoriesResponse;
            } else {
                $this->error('ZDDK API response format unexpected for categories. Contains status/products keys but no "categories" array or direct category list.');
                Log::error('ZDDK Category Sync Error - Unexpected Response Structure', ['response' => $zddkCategoriesResponse]);
                return;
            }
        } else {
            $this->error('Failed to fetch or parse categories from ZDDK API. Response was: ' . json_encode($zddkCategoriesResponse));
            Log::error('ZDDK Category Sync Error - Invalid or Empty Response', ['response' => $zddkCategoriesResponse]);
            return;
        }

        if (empty($categoriesToProcess)) {
            $this->warn('No categories found from ZDDK API to sync.');
            return;
        }

        foreach ($categoriesToProcess as $zddkCategory) {
            if (!isset($zddkCategory['id']) || empty($zddkCategory['name']) || $zddkCategory['name'] === 'null') {
                $this->warn("Skipping category due to missing ID or invalid name: " . json_encode($zddkCategory));
                continue;
            }

            // Use firstOrNew with zddk_category_id for matching
            $category = Category::firstOrNew(['zddk_category_id' => $zddkCategory['id']]);

            // If it's a new category, ensure name is set
            if (!$category->exists) {
                $category->name = $zddkCategory['name'];
            } else {
                // If exists, update name if it changed
                $category->name = $zddkCategory['name'];
            }
            $category->zddk_category_id = $zddkCategory['id']; // Ensure ID is set

            $rawCatImagePath = $zddkCategory['image_path']
                                ?? $zddkCategory['img']
                                ?? $zddkCategory['image']
                                ?? $zddkCategory['icon']
                                ?? null;

            if ($rawCatImagePath) {
                if (str_starts_with($rawCatImagePath, 'http://localhost:8000/storage/')) {
                    $category->image_path = str_replace('http://localhost:8000/storage/', '', $rawCatImagePath);
                } else {
                    $category->image_path = $rawCatImagePath;
                }
            } else {
                $category->image_path = null;
            }

            $category->save();

            $this->info("Synced category: " . $zddkCategory['name'] . " (ZDDK ID: " . $zddkCategory['id'] . ")");
        }
        $this->info('Categories sync finished.');
    }

    protected function syncProducts()
    {
        $this->info('Syncing ZDDK products...');
        $zddkProductsResponse = $this->zddkApi->getProducts();

        $productsToProcess = [];
        if (isset($zddkProductsResponse['products']) && is_array($zddkProductsResponse['products'])) {
            $productsToProcess = $zddkProductsResponse['products'];
        } elseif (is_array($zddkProductsResponse) && !empty($zddkProductsResponse)) {
            if (!isset($zddkProductsResponse['status']) && !isset($zddkProductsResponse['categories'])) {
                $productsToProcess = $zddkProductsResponse;
            } else {
                $this->error('ZDDK API response format unexpected for products. Contains status/categories keys but no "products" array or direct product list.');
                Log::error('ZDDK Product Sync Error - Unexpected Response Structure', ['response' => $zddkProductsResponse]);
                return;
            }
        } else {
            $this->error('Failed to fetch or parse products from ZDDK API. Response was: ' . json_encode($zddkProductsResponse));
            Log::error('ZDDK Product Sync Error - Invalid or Empty Response', ['response' => $zddkProductsResponse]);
            return;
        }

        if (empty($productsToProcess)) {
            $this->warn('No products found from ZDDK API to sync.');
            return;
        }

        // Get the default "Uncategorized" category ID once
        $uncategorized = Category::firstOrCreate(
            ['name' => 'غير مصنف'],
            ['zddk_category_id' => 0] // Assign a default ZDDK ID for this placeholder
        );
        $defaultCategoryId = $uncategorized->id;

        foreach ($productsToProcess as $zddkProduct) {
            if (!isset($zddkProduct['id'])) {
                $this->warn('Skipping ZDDK product due to missing ID: ' . json_encode($zddkProduct));
                continue;
            }

            $service = Service::firstOrNew(['zddk_product_id' => $zddkProduct['id']]);

            $zddkCategoryId = $zddkProduct['parent_id'] ?? null; // ZDDK API uses 'parent_id' for product category

            $category = null;
            if ($zddkCategoryId) {
                $category = Category::where('zddk_category_id', $zddkCategoryId)->first();
            }

            // Fallback: If category not found by ZDDK ID, try by category_name
            if (!$category && isset($zddkProduct['category_name'])) {
                $category = Category::where('name', $zddkProduct['category_name'])->first();
            }

            // Assign category_id: Use found category or default to "Uncategorized"
            if ($category) {
                $service->category_id = $category->id;
            } else {
                $categoryIdForLog = $zddkCategoryId ?? 'N/A';
                $categoryNameForLog = $zddkProduct['category_name'] ?? 'N/A';
                $this->warn("Category '{$categoryNameForLog}' (ZDDK ID: {$categoryIdForLog}) not found for product {$zddkProduct['id']}. Assigning to '{$uncategorized->name}'.");
                $service->category_id = $defaultCategoryId; // <--- استخدم الفئة الافتراضية هنا
            }

            $service->title = $zddkProduct['name'] ?? 'Untitled ZDDK Product';
            $service->description = $zddkProduct['desc'] ?? 'No description provided.';
            $service->price = $zddkProduct['price'] ?? 0.00;

            $rawImagePath = $zddkProduct['category_img']
                                ?? $zddkProduct['product_img']
                                ?? $zddkProduct['image']
                                ?? null;

            if ($rawImagePath) {
                if (str_starts_with($rawImagePath, 'http://localhost:8000/storage/')) {
                    $service->image_path = str_replace('http://localhost:8000/storage/', '', $rawImagePath);
                } else {
                    $service->image_path = $rawImagePath;
                }
            } else {
                $service->image_path = null;
            }

            $service->is_zddk_product = true;
            $service->product_type = $zddkProduct['product_type'] ?? null;

            $paramsToEncode = [];
            if (isset($zddkProduct['params'])) {
                if (is_array($zddkProduct['params'])) {
                    $paramsToEncode = $zddkProduct['params'];
                } elseif (is_string($zddkProduct['params']) && !empty($zddkProduct['params'])) {
                    $paramsToEncode = [$zddkProduct['params']];
                }
            }
            $service->zddk_required_params = json_encode($paramsToEncode);

            $qtyValuesToEncode = [];
            if (isset($zddkProduct['qty_values'])) {
                if (is_array($zddkProduct['qty_values']) || is_object($zddkProduct['qty_values'])) {
                    $qtyValuesToEncode = $zddkProduct['qty_values'];
                } elseif (is_string($zddkProduct['qty_values'])) {
                    $decodedQty = json_decode($zddkProduct['qty_values'], true);
                    if (json_last_error() === JSON_ERROR_NONE && (is_array($decodedQty) || is_object($decodedQty))) {
                        $qtyValuesToEncode = $decodedQty;
                    } else {
                        $qtyValuesToEncode = [$zddkProduct['qty_values']];
                    }
                }
            }
            $service->zddk_qty_values = json_encode($qtyValuesToEncode);

            $service->save();
            $this->info("Synced product: " . $service->title . " (ZDDK ID: " . $zddkProduct['id'] . ")");
        }
        $this->info('Products sync finished.');
    }
}
