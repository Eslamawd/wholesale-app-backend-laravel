<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZddkApiService;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Support\Facades\Log; // Ensure Log facade is imported

class SyncZddkProducts extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sync:zddk-data'; // Changed signature for broader scope

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Synchronize products and categories from ZDDK API to local database.';

    protected $zddkApi;

    /**
     * Create a new command instance.
     *
     * @param ZddkApiService $zddkApi
     */
    public function __construct(ZddkApiService $zddkApi)
    {
        parent::__construct();
        $this->zddkApi = $zddkApi;
    }

    /**
     * Execute the console command.
     * @return int
     */
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
        // Robust handling of API response for categories
        if (isset($zddkCategoriesResponse['categories']) && is_array($zddkCategoriesResponse['categories'])) {
            $categoriesToProcess = $zddkCategoriesResponse['categories'];
        } elseif (is_array($zddkCategoriesResponse) && !empty($zddkCategoriesResponse)) {
            // This case handles a direct array of categories without a 'categories' key,
            // but ensure it's not a status/product response if it implies an error
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
            // Skip categories with missing ID or invalid name
            if (!isset($zddkCategory['id']) || empty($zddkCategory['name']) || $zddkCategory['name'] === 'null') {
                $this->warn("Skipping category due to missing ID or invalid name: " . json_encode($zddkCategory));
                continue;
            }

            // Always prioritize ZDDK category ID for lookup
            $category = Category::firstOrNew(['zddk_category_id' => $zddkCategory['id']]);

            // If a new category is being created, ensure it has a valid ID
            // This check ensures we don't try to save a new category without its ZDDK ID
            if (!$category->exists && is_null($category->zddk_category_id)) {
                // Fallback to name if ZDDK ID is still somehow missing,
                // but this should ideally not happen if ZDDK provides IDs consistently.
                $category = Category::firstOrNew(['name' => $zddkCategory['name']]);
                if (!$category->exists) {
                    $this->warn("Category ID missing for '{$zddkCategory['name']}'. Creating by name. Consider checking ZDDK API for consistent IDs.");
                }
            }

            $category->name = $zddkCategory['name'];
            $category->zddk_category_id = $zddkCategory['id']; // Ensure ID is set

            // --- START: MODIFIED IMAGE PATH FOR CATEGORIES ---
            $rawCatImagePath = $zddkCategory['image_path'] // Prefer 'image_path'
                                ?? $zddkCategory['img']
                                ?? $zddkCategory['image']
                                ?? $zddkCategory['icon']
                                ?? null;

            if ($rawCatImagePath) {
                // Check if the path contains the incorrect localhost storage prefix
                if (str_starts_with($rawCatImagePath, 'http://localhost:8000/storage/')) {
                    // Remove the incorrect prefix to get the actual external URL
                    $category->image_path = str_replace('http://localhost:8000/storage/', '', $rawCatImagePath);
                } else {
                    // If it's a clean external URL or local path, use it as is
                    $category->image_path = $rawCatImagePath;
                }
            } else {
                $category->image_path = null;
            }
            // --- END: MODIFIED IMAGE PATH FOR CATEGORIES ---

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
        // Robust handling of API response for products
        if (isset($zddkProductsResponse['products']) && is_array($zddkProductsResponse['products'])) {
            $productsToProcess = $zddkProductsResponse['products'];
        } elseif (is_array($zddkProductsResponse) && !empty($zddkProductsResponse)) {
            // This case handles a direct array of products without a 'products' key,
            // but ensure it's not a status/category response if it implies an error
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

        foreach ($productsToProcess as $zddkProduct) {
            // Ensure product ID exists before processing
            if (!isset($zddkProduct['id'])) {
                $this->warn('Skipping ZDDK product due to missing ID: ' . json_encode($zddkProduct));
                continue;
            }

            $service = Service::firstOrNew(['zddk_product_id' => $zddkProduct['id']]);

            // Find category using ZDDK's parent_id
            $zddkCategoryId = $zddkProduct['parent_id'] ?? null;
            $category = null;

            if ($zddkCategoryId) {
                // Find category by zddk_category_id (synced in syncCategories)
                $category = Category::where('zddk_category_id', $zddkCategoryId)->first();
            }

            // If category not found by ZDDK ID, try by category_name (fallback)
            if (!$category && isset($zddkProduct['category_name'])) {
                $category = Category::where('name', $zddkProduct['category_name'])->first();
            }

            // If category still not found, create a placeholder category
            // (Less ideal, as categories should ideally be synced first)
            if (!$category) {
                $categoryIdForLog = $zddkCategoryId ?? 'N/A';
                $categoryNameForLog = $zddkProduct['category_name'] ?? 'N/A';
                $this->warn("Category '{$categoryNameForLog}' (ZDDK ID: {$categoryIdForLog}) not found for product {$zddkProduct['id']}. This product will be uncategorized.");
                // Option 1: Set category_id to null and log warning
                $service->category_id = null;
                // Option 2: Create a generic "Uncategorized" category if it doesn't exist
                // $uncategorized = Category::firstOrCreate(['name' => 'Uncategorized ZDDK', 'zddk_category_id' => 0]); // Assuming 0 for generic ZDDK uncategorized
                // $service->category_id = $uncategorized->id;
            } else {
                $service->category_id = $category->id;
            }

            $service->title = $zddkProduct['name'] ?? 'Untitled ZDDK Product'; // Add default for title
            $service->description = $zddkProduct['desc'] ?? 'No description provided.';
            $service->price = $zddkProduct['price'] ?? 0.00; // Ensure price defaults to 0.00 if missing or null from API
            
            // --- START: MODIFIED IMAGE PATH FOR SERVICES ---
            $rawImagePath = $zddkProduct['category_img'] // Common in ZDDK product response
                                ?? $zddkProduct['product_img']
                                ?? $zddkProduct['image']
                                ?? null;

            if ($rawImagePath) {
                // Check if the path contains the incorrect localhost storage prefix
                if (str_starts_with($rawImagePath, 'http://localhost:8000/storage/')) {
                    // Remove the incorrect prefix to get the actual external URL
                    $service->image_path = str_replace('http://localhost:8000/storage/', '', $rawImagePath);
                } else {
                    // If it's a clean external URL or local path, use it as is
                    $service->image_path = $rawImagePath;
                }
            } else {
                $service->image_path = null;
            }
            // --- END: MODIFIED IMAGE PATH FOR SERVICES ---
            
            $service->is_zddk_product = true; // Mark as ZDDK product
            $service->product_type = $zddkProduct['product_type'] ?? null;
            
            // --- START: MODIFIED ZDDK REQUIRED PARAMS ---
            $paramsToEncode = [];
            if (isset($zddkProduct['params'])) {
                if (is_array($zddkProduct['params'])) {
                    $paramsToEncode = $zddkProduct['params'];
                } elseif (is_string($zddkProduct['params']) && !empty($zddkProduct['params'])) {
                    // If it's a non-empty string, assume it's a single parameter and wrap it in an array
                    $paramsToEncode = [$zddkProduct['params']];
                }
            }
            $service->zddk_required_params = json_encode($paramsToEncode);
            // --- END: MODIFIED ZDDK REQUIRED PARAMS ---

            // --- START: MODIFIED ZDDK QTY VALUES ---
            $qtyValuesToEncode = []; // Default to an empty array
            if (isset($zddkProduct['qty_values'])) {
                if (is_array($zddkProduct['qty_values']) || is_object($zddkProduct['qty_values'])) {
                    $qtyValuesToEncode = $zddkProduct['qty_values'];
                } elseif (is_string($zddkProduct['qty_values'])) {
                    $decodedQty = json_decode($zddkProduct['qty_values'], true);
                    if (json_last_error() === JSON_ERROR_NONE && (is_array($decodedQty) || is_object($decodedQty))) {
                        $qtyValuesToEncode = $decodedQty;
                    } else {
                        // If it was a plain string (e.g., "500"), wrap it in an array
                        $qtyValuesToEncode = [$zddkProduct['qty_values']];
                    }
                }
            }
            $service->zddk_qty_values = json_encode($qtyValuesToEncode);
            // --- END: MODIFIED ZDDK QTY VALUES ---

            $service->save();
            $this->info("Synced product: " . $zddkProduct['name'] . " (ZDDK ID: " . $zddkProduct['id'] . ")");
        }
        $this->info('Products sync finished.');
    }
}