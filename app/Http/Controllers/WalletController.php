<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    //

    public function balance(Request $request)
{
    $user = $request->user();

    return response()->json([
        'balance' => $user->balanceInt / 100  // عرض الرصيد بالدولار
    ]);
}

   public function deposit($id, Request $request)
{
    $request->validate([
        'amount' => 'required|numeric|min:0.01',
    ]);

    $user = User::findOrFail($id);

    $amountInCents = (int) round($request->amount * 100); // تحويل لـ سنتات

    $user->deposit($amountInCents);

    return response()->json([
        'user' => new UserResource($user),
        'new_balance' => $user->balanceInt / 100  // عرض الرصيد بالدولار
    ]);
}

   
public function withdraw($id, Request $request)
{
    $request->validate([
        'amount' => 'required|numeric|min:0.01',
    ]);

    $user = User::findOrFail($id);

    $amountInCents = (int) round($request->amount * 100); // تحويل لـ سنتات

    $user->withdraw($amountInCents);

    return response()->json([
        'user' => new UserResource($user),
        'new_balance' => $user->balanceInt / 100  // عرض الرصيد بالدولار
    ]);
}

}
