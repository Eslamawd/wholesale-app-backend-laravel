<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    //

public function index () {
    $discount = Discount::first();
    
        return response()->json(['discount' => $discount]);

}


    public function discount (Request $request) {
          $validated = $request->validate([
            'price_percentage_user'       => 'required|integer|min:1|max:100',
            'price_percentage_seals' => 'required|integer|min:1|max:100',
            'user_spend_threshold'      => 'required|integer|min:1',
            'seals_spend_threshold'      => 'required|integer|min:1',
        ]);
        $discount = Discount::first();
        if ($discount) {
            $discount->update($validated);
        } else {
            $discount = Discount::create($validated);
        }

        return response()->json(['discount' => $discount, 'message' => 'Discount Is Succsess' ]);
    }

}
