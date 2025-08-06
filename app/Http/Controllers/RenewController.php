<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Renew;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RenewController extends Controller
{
    //


    public function index() {
        $renews = Renew::with([
            'subscription.product',
            'subscription.user',
            'subscription.accounts',

        ])->paginate(6);
        return response()->json(['renews'=> $renews]);

    }

    public function store(Request $request) {
        
        $validated = $request->validate([
        'subscription_id' => 'required|exists:subscriptions,id',
        'duration' => 'required|in:1_month,3_months,6_months,1_year',
    ]);
      $user = Auth::user();
      $subscription = Subscription::findOrFail($validated['subscription_id']);
       if ($subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this subscription.');
        }

        $service = Product::findOrFail($subscription->product_id);

       $basePrice = $user->role === 'seals' && $service->price_wholesale
        ? $service->price_wholesale
        : $service->price;
        
    $total = match ($validated['duration']) {
        '1_month'   => $basePrice,
        '3_months'  => $basePrice * 3,
        '6_months'  => $basePrice * 6,
        '1_year'    => $basePrice * 12,
    };

    $totalInCents = (int) round($total * 100); // سنت
    $balance = $user->balanceInt; // سنت
    
        if ($balance < $totalInCents) {
            throw ValidationException::withMessages([
                'wallet' => 'Your wallet balance is insufficient.',
            ]);
        }

        // اسحب من المحفظة
        $user->withdraw($totalInCents);
  $now = now();
$startsAt = optional($subscription->ends_at)->gt($now)
    ? $subscription->ends_at
    : $now;

    $endsAt = match ($validated['duration']) {
        '1_month'   => $startsAt->copy()->addMonth(),
        '3_months'  => $startsAt->copy()->addMonths(3),
        '6_months'  => $startsAt->copy()->addMonths(6),
        '1_year'    => $startsAt->copy()->addYear(),
    };

    $renew = Renew::create([
        'user_id'    => $user->id,
        'duration'   => $validated['duration'],
        'subscription_id' => $validated['subscription_id']

    ]);

    $subscription->update([
        'duration'   => $validated['duration'],
        'status'     => 'pending',
        'total' => $total,
        'starts_at'  => $startsAt,
        'ends_at'    => $endsAt,
    ]);

    return response()->json([
        'message' => 'Renew created successfully',
        'renew' => $renew,
    ], 201);

    }
}
