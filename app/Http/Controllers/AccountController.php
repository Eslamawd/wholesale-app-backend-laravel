<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    //
  


    public function store(Request $request)
{
   $validated =  $request->validate([
        'email'     => 'required|email',
        'password'     => 'required|string',
        'subscription_id' => 'required|integer|exists:subscriptions,id',
    ]);

   

    $account = Account::create($validated);

    return response()->json(['account' => $account], 201);
}


}
