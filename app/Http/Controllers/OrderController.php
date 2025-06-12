<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\Service;
use Auth;
use DB;
use Illuminate\Http\Request;
use Log;

class OrderController extends Controller
{
   public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('services')->get();
        return response()->json(['orders'=> $orders]);
    }

    public function store(Request $request)
    {
        $total = 0;
        foreach ($request->services as $item) {
            $service = Service::find($item['id']);
            $total += $service->price * $item['quantity'];
        }

        $user = $request->user();
        $wallet = $user->balance;
        $used_balance = min($wallet, $total);
        $remaining = $total - $used_balance;

        try {
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $total,
                'payment_status' => $remaining > 0 ? 'pending' : 'completed',
                'used_balance' => $used_balance,
                'payment_method' => $request->payment_method ?? 'balance',
            ]);

            foreach ($request->services as $item) {
                $service = Service::find($item['id']);
                $order->services()->attach($service->id, [
                    'quantity' => $item['quantity'],
                    'price' => $service->price
                ]);
                if(!$service) {
                    throw new \Exception('Service not found: ' . $item['id']);
                }
            }

            $user->withdraw($used_balance);
            $user->save();

          
            return response()->json($order->load('services'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create order'], 500);
        }
    }

    public function update(Request $request, $id ) {
        $order =  Order::findOrFail($id);
        
        $request->validate(['payment_status' => 'required']);
        $order->update(['payment_status' => $request->payment_status]);
        $order->save();
        return response()->json(['message'=> 'Create Sucsess']);

    }
    

    public function show ($id) {
        $order = Order::find($id);
        $order->with('services')->get();
        return response()->json(['order'=>$order ]);

    }
    
    public function getRevenue()
    {
        $revenue = Order::sum('total_price'); // أو أي طريقة لحساب الإيرادات
        return response()->json(['count' => $revenue]);
    }
    public function count()
    {
        $count = Order::count();
        return response()->json(['count' => $count]);
    }
  
    public function orders()
    {
          $orders = Order::with( ['services', 'user'])->get();
        return response()->json(['orders' => $orders]);
    }
}

