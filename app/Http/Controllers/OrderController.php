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
        $orders = $request->user()->orders()->with('product')->paginate(6);
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
    $total = $product->price * $validated['count'];
    $user = $request->user();
    $total = $product->price * $validated['count']; // دولار
    $totalInCents = (int) round($total * 100); // سنت
    $balance = $user->balanceInt; // سنت

   if ($balance < $totalInCents) {
     return response()->json(['message' => 'Insufficient balance'], 422);
   }

    // تنسيق الحقول لإرسالها لـ 3BE
    $formattedFields = $this->formatUserFields($validated['user_fields']);

    // إرسال الطلب لـ 3BE أولاً قبل إنشاء الأوردر أو الخصم
    $payload = [[
        'item_id' => $product->external_id,
        'count' => $validated['count'],
        'user_fields' => $formattedFields,
    ]];

    $response = $this->cardService->sendOrder($payload);
    $data = $response->json();

    // تحقق من نجاح الطلب
    if ($response->successful() && empty($data['error'])) {
        // خصم الرصيد بعد النجاح
        $user->withdraw($totalInCents);

        // حفظ الأوردر بعد نجاح الدفع ونجاح الطلب من 3BE
        $order = Order::create([
            'product_id' => $product->id,
            'count' => $validated['count'],
            'user_fields' => $validated['user_fields'], // النسخة الخام لو تحب
            'user_id' => $user->id,
            'total_price' => $total,
            'external_order_id' => $data['order_id'] ?? null,
            'response' => $data,
        ]);

        return response()->json([
            'message' => 'Order sent successfully.',
            'order' => $order,
        ]);
    }

    return response()->json([
        'message' => 'Failed To Send Order',
        'error' => $data,
    ], 422);
}


    protected function formatUserFields(array $fields): array
    {
        $formatted = [];

        foreach ($fields as $field) {
            if (isset($field['field_name']) && isset($field['value'])) {
                $formatted[$field['field_name']] = $field['value'];
            }
        }

        return $formatted;
    }

    public function show($id)
    {
        $order = Order::with('product')->find($id);
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
        $orders = Order::with(['product', 'user'])->paginate(6);
        return response()->json(['orders' => $orders]);
    }
}
