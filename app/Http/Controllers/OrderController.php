<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\ThreeBECardService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
       protected $cardService;

    public function __construct(ThreeBECardService $cardService)
    {
        $this->cardService = $cardService;
    }

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('product')->get();
        return response()->json(['orders' => $orders]);
    }

   public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'count' => 'required|integer|min:1',
            'user_fields' => 'required|array',

        ]);

        $product = Product::findOrFail($validated['product_id']);

        if (request()->user()->wallet->balance < $product->price * $validated['count']) {
            return response()->json(['message' => 'Insufficient balance'], 422);
        }
        // 1. احفظ الأوردر محليًا
        $order = Order::create([
            'product_id' => $product->id,
            'count' => $validated['count'],
            'user_fields' => $validated['user_fields'],
            'user_id' => $request->user()->id,
            'total_price' => $product->price * $validated['count'],
            
        ]);


        // خصم المبلغ من رصيد المستخدم
        $request->user()->withdraw( $order->total_price);

        // 2. جهز البيانات لإرسالها لـ 3BE
        $payload = [
            [
                'item_id' => $product->external_id,
                'count' => $validated['count'],
                'user_fields' => $validated['user_fields'],
            ]
        ];

        // 3. أرسل الأوردر
        $response = $this->cardService->sendOrder($payload);

        if ($response->successful()) {
            $order->external_order_id = $response['order_id'] ?? null;
            $order->response = $response->json();
            $order->save();

            return response()->json([
                'message' => 'Order sent successfully.',
                'order' => $order,
            ]);
        } else {
            return response()->json([
                'message' => 'Failed to send order to 3BE.',
                'error' => $response->json(),
            ], 422);
        }
    }
    // ... باقي دوال الـ Controller ...

  

    public function show($id)
    {
        $order = Order::with('product')->find($id); // استخدام with مباشرة مع find
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json(['order' => $order]);
    }

    public function getRevenue()
    {
        $revenue = Order::sum('total_price');
        return response()->json(['count' => $revenue]);
    }

    public function count()
    {
        $count = Order::count();
        return response()->json(['count' => $count]);
    }

    public function orders()
    {
        $orders = Order::with(['products', 'user'])->get();
        return response()->json(['orders' => $orders]);
    }
}