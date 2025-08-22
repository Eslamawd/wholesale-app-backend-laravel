<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    //

public function index () {
    $discount = Discount::with('category')->paginate(10);

        return response()->json(['discount' => $discount]);

}


    public function discount (Request $request) {
          $validated = $request->validate([
            'category_id'                => 'required|exists:categories,id',
            'price_percentage_user'       => 'required|integer|min:1|max:100',
            'price_percentage_seals' => 'required|integer|min:1|max:100',
            'user_spend_threshold'      => 'required|integer|min:1',
            'seals_spend_threshold'      => 'required|integer|min:1',
        ]);

        $discount = Discount::updateOrCreate(['category_id' => $validated['category_id']], $validated);
        $discount->load('category');

        return response()->json(['discount' => $discount, 'message' => 'Discount Is Succsess' ]);
    }

}
