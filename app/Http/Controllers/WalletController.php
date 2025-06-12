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
        $user = $request->user(); // Authenticated user
        return response()->json(['balance' => $user->balance]);
    }

    public function deposit($id, Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = User::findOrFail($id);

        $user->deposit($request->amount);
         $user->wallet_balance = $user->balance;

        return response()->json(['user' => new UserResource( $user),]);
    }

    public function withdraw($id ,Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = User::findOrFail($id);

        

        $user->withdraw($request->amount);
         $user->wallet_balance = $user->balance;

        return response()->json(['user' => new UserResource( $user)]);
    }

}
