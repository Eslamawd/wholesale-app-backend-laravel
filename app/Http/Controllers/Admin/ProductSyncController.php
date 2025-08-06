<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncCategoriesFromAPI;
use App\Jobs\SyncProductsFromAPI;
use Illuminate\Http\Request;

class ProductSyncController extends Controller
{
    //
    public function syncProdacts(Request $request) {

        $validated = $request->validate([
            'percentage' => 'required|integer|min:1|max:100',
            'percentage_seals' => 'required|integer|min:1|max:100',
        ]);
        SyncCategoriesFromAPI::dispatch();
        SyncProductsFromAPI::dispatch($validated);
       return response()->json(['message' => 'Updated Sync Products From Api']);
    }
}
