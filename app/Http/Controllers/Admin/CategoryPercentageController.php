<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncPercentageCategories;
use Illuminate\Http\Request;

class CategoryPercentageController extends Controller
{
    //
    

     public function percentage(Request $request) {

        $validated = $request->validate([
            'percentage'       => 'required|integer|min:1|max:100',
            'percentage_seals' => 'required|integer|min:1|max:100',
            'category_id'      => 'required|exists:categories,id',
        ]);
        SyncPercentageCategories::dispatch($validated);
       return response()->json(['message' => 'Updated Percentage Products in Category']);
    }
}
