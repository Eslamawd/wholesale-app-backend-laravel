<?php

namespace App\Http\Controllers;

use App\Models\UserSeals;
use Illuminate\Http\Request;

class UserSealsController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = $request->user();
        $userSeals = $user->userSeals()->with([
            'allSubs.subscription.accounts',
            'allSubs.subscription.product',
            'allSubs.order.product',
            
            ])->paginate(6);

        return response()->json( $userSeals);
    }
    public function getAllUserBySealer(Request $request)
    {
        $user = $request->user();
        $userSeals = $user->userSeals()->get();

        return response()->json( $userSeals);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:15',
            'name' => 'required|string|max:255',
        ]);

        $userSeal = $request->user()->userSeals()->create($data);

        return response()->json(['user' => $userSeal], 201);
    }
    public function show($id)
    {
        $userSeal = UserSeals::with('allSubs')->findOrFail($id)->paginate(6);

        return response()->json($userSeal);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:15',
            'name' => 'required|string|max:255',
        ]);

        $userSeal = UserSeals::findOrFail($id);
        $userSeal->update($data);

        return response()->json($userSeal);
    }

    public function destroy($id)
    {
        $userSeal = UserSeals::findOrFail($id);
        $userSeal->delete();

        return response()->json(['message' => 'User Seal deleted successfully']);
    }



    public function createNewSub(Request $request,$id)
    {
      
        $data = $request->validate([
            'subscription_id' => 'exists:subscriptions,id',
            'order_id' => 'exists:orders,id',
            'total' => 'required'
        ]);

        $userSeal = UserSeals::findOrFail($id);
        $allSub = $userSeal->allSubs()->create($data);

        return response()->json($allSub, 201);
    }

    public function destroySub($id, $subId)
    {
        $userSeal = UserSeals::findOrFail($id);
        $allSub = $userSeal->allSubs()->findOrFail($subId);
        $allSub->delete();

        return response()->json(['message' => 'Subscription deleted successfully']);
    }

}
