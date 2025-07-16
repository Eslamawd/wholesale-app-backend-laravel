<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionResource;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{

public function index(Request $request)
{
    $subscriptions = $request->user()->subscriptions()
        ->with(['product', 'accounts'])
        ->paginate(6);

    return response()->json($subscriptions);
}
public function admin(Request $request)
{
    $subscriptions = Subscription::with(['user', 'product', 'accounts'])->paginate(6);

    return response()->json([
        'subscriptions' => $subscriptions,
    ]);
}


    public function store(Request $request)
{
    $validated = $request->validate([
        'product_id' => 'required|exists:products,id',
        'duration' => 'required|in:1_month,3_months,6_months,1_year',
    ]);

    $user = Auth::user();
    $service = Product::findOrFail($validated['product_id']);

    // احسب السعر حسب المدة
   $basePrice = $user->role === 'seals' && $service->price_wholesale
    ? $service->price_wholesale
    : $service->price;


    $total = match ($validated['duration']) {
        '1_month'   => $basePrice,
        '3_months'  => $basePrice * 3,
        '6_months'  => $basePrice * 6,
        '1_year'    => $basePrice * 12,
    };

    
        if ($user->balance < $total) {
            throw ValidationException::withMessages([
                'wallet' => 'Your wallet balance is insufficient.',
            ]);
        }

        // اسحب من المحفظة
        $user->withdraw($total);
  
    $startsAt = now();
    $endsAt = match ($validated['duration']) {
        '1_month'   => $startsAt->copy()->addMonth(),
        '3_months'  => $startsAt->copy()->addMonths(3),
        '6_months'  => $startsAt->copy()->addMonths(6),
        '1_year'    => $startsAt->copy()->addYear(),
    };

    $subscription = Subscription::create([
        'user_id'    => $user->id,
        'product_id' => $service->id,
        'duration'   => $validated['duration'],
        'status'     => 'pending',
        'starts_at'  => $startsAt,
        'ends_at'    => $endsAt,
    ]);

    return response()->json([
        'message' => 'Subscription created successfully',
        'subscription' => $subscription,
    ], 201);
}





    public function show($id)
    {
        $subscription = Subscription::with(['user', 'product'])->findOrFail($id);
        return response()->json($subscription);
    }



        public function changeStatus(Request $request,  $id)
{
    
        $sub = Subscription::findOrFail($id);

        $request->validate(['status' => 'required|in:pending,expired,active']);

        $sub->status = $request->status;

        $sub->save();

        $sub->with(['user', 'product'])->get();

   
       
       

    return response()->json(['subscription' => $sub]);
}

    public function destroy($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();
        return response()->json(['message' => 'Subscription deleted']);
    }
}
