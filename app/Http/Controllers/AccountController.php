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
    public function update(Request $request, $id)
{

    $account = Account::findOrFail($id);

   $validated =  $request->validate([
        'email'     => 'required|email',
        'password'     => 'required|string',
        'subscription_id' => 'required|integer|exists:subscriptions,id',
    ]);

   

    $account->update($validated);

    return response()->json(['account' => $account], 201);
}
    public function destroy( $id)
{

    $account = Account::findOrFail($id);

    $account->delete();


    return response()->json(['message' => 'Deleted succsess'], 201);
}


}
