<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest; // 
use App\Models\Order;
use App\Models\Service;
use App\Services\ZddkApiService; // استيراد الـ Service Class
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // لاستخدام الـ Transactions
use Illuminate\Support\Facades\Log; // لاستخدام الـ Logging
use Illuminate\Support\Str; // لاستخدام Str::uuid() لتوليد UUID

class OrderController extends Controller
{
    protected $zddkApi;

    // حقن ZddkApiService في الـ Constructor
    public function __construct(ZddkApiService $zddkApi)
    {
        $this->zddkApi = $zddkApi;
    }

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('services')->get();
        return response()->json(['orders' => $orders]);
    }

    public function store(Request $request)
    {
        // 1. بدء Transaction لضمان سلامة البيانات
        // إذا فشل أي جزء (الدفع المحلي أو طلب ZDDK)، يتم التراجع عن كل شيء.
        DB::beginTransaction();

        try {
            $total = 0;
            $orderServices = []; // لتخزين الخدمات المراد إضافتها للطلب
            $zddkOrderDetails = null; // لتخزين تفاصيل طلب ZDDK إذا وجد

            // التحقق من صلاحية الطلب
            $request->validate([
                'services' => 'required|array|min:1',
                'services.*.id' => 'required|integer|exists:services,id',
                'services.*.quantity' => 'required|integer|min:1',
                // أضف هنا validation لـ extra_params لو كان مطلوب
                // 'services.*.extra_params' => 'nullable|array',
                'payment_method' => 'nullable|string', // تأكد أن هذا الحقل يأتي من الـ frontend
            ]);


            foreach ($request->services as $item) {
                $service = Service::find($item['id']);

                if (!$service) {
                    throw new \Exception('Service not found: ' . $item['id']);
                }

                $orderServices[] = [
                    'service' => $service,
                    'quantity' => $item['quantity'],
                    'extra_params' => $item['extra_params'] ?? [], // استقبال البارامترات الإضافية من الـ frontend
                ];

                $total += $service->price * $item['quantity'];
            }

            $user = $request->user();
            $wallet = $user->balance;
            $used_balance = min($wallet, $total);
            $remaining = $total - $used_balance;

            // 2. إنشاء الطلب في قاعدة بياناتك المحلية أولاً
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $total,
                'payment_status' => $remaining > 0 ? 'pending' : 'completed', // قد يتغير لاحقاً بناءً على ZDDK
                'used_balance' => $used_balance,
                'payment_method' => $request->payment_method ?? 'balance',
                // 'zddk_order_id' => null, // سيتم تحديثه لاحقاً إذا كان منتج ZDDK
                // 'zddk_order_uuid' => null, // سيتم تحديثه لاحقاً إذا كان منتج ZDDK
                // 'zddk_status' => null, // سيتم تحديثه لاحقاً إذا كان منتج ZDDK
                // 'zddk_delivery_data' => null, // سيتم تحديثه لاحقاً إذا كان منتج ZDDK
            ]);

            // 3. خصم المبلغ من محفظة المستخدم المحلية
            // يجب أن يكون لديك دالة withdraw في موديل المستخدم (User Model) أو في Trait
            $user->withdraw($used_balance);
            $user->save(); // حفظ التغييرات على رصيد المستخدم

            // 4. ربط الخدمات بالطلب ومعالجة طلبات ZDDK API
            foreach ($orderServices as $orderItem) {
                $service = $orderItem['service'];
                $quantity = $orderItem['quantity'];
                $extraParams = $orderItem['extra_params'];

                // ربط الخدمة المحلية بالطلب
                $order->services()->attach($service->id, [
                    'quantity' => $quantity,
                    'price' => $service->price
                ]);

                // 5. إذا كانت الخدمة من ZDDK API، قم بإرسال الطلب لهم
                if ($service->is_zddk_product && $service->zddk_product_id) {
                    $zddkOrderUuid = (string) Str::uuid(); // توليد UUID فريد لطلب ZDDK

                    $zddkResponse = $this->zddkApi->createOrder(
                        $service->zddk_product_id,
                        $quantity,
                        $extraParams,
                        $zddkOrderUuid
                    );

                    if ($zddkResponse && isset($zddkResponse['status']) && $zddkResponse['status'] === 'OK') {
                        // تحديث تفاصيل طلب ZDDK في طلبك المحلي (إذا كان هناك حقول مضافة في موديل Order)
                        $order->update([
                            'zddk_order_id' => $zddkResponse['data']['order_id'], // استخدم 'order_id' كما في الوثائق
                            'zddk_order_uuid' => $zddkOrderUuid,
                            'zddk_status' => $zddkResponse['data']['status'], // 'wait' في البداية غالباً
                            'payment_status' => 'processing', // أو أي حالة تعكس الانتظار من ZDDK
                        ]);
                        // يمكنك هنا حفظ الـ replay_api إذا كانت تحتوي على بيانات مهمة فورية
                        // $order->zddk_delivery_data = json_encode($zddkResponse['data']['replay_api'] ?? null);
                        // $order->save();

                        // إذا كان هناك أكثر من منتج ZDDK في نفس الطلب، ستحتاج لآلية لحفظ تفاصيل كل طلب ZDDK على حدة.
                        // حالياً، هذا الكود يفترض أن الطلب الواحد قد يحتوي على منتج ZDDK واحد يتم تخزين تفاصيله في حقول Order مباشرة.
                        // لو فيه أكتر من واحد، هتحتاج جدول Many-to-Many بين Order و ZDDK_Orders مثلاً.
                        $zddkOrderDetails = $zddkResponse; // لتأكيد النجاح العام للعملية
                    } else {
                        // فشل طلب ZDDK API
                        throw new \Exception('Failed to place order with ZDDK API for product ID: ' . $service->zddk_product_id . '. Response: ' . json_encode($zddkResponse));
                    }
                }
            }

            // إذا كل شيء سار بنجاح (الدفع المحلي وطلب ZDDK إن وجد)
            DB::commit(); // تأكيد الـ Transaction

            // قد تحتاج لتغيير حالة الدفع النهائية للطلب المحلي هنا
            // بناءً على ما إذا كانت هناك حاجة لمعالجة API خارجي أم لا.
            // إذا كان المنتج ZDDK، فالحالة هتكون 'processing' في البداية.
            // إذا لم يكن ZDDK، والـ remaining = 0، فـ 'completed'.

            return response()->json($order->load('services'));

        } catch (\Exception $e) {
            DB::rollBack(); // التراجع عن كل التغييرات في قاعدة البيانات
            Log::error('Order creation failed: ' . $e->getMessage(), ['user_id' => $user->id, 'request_data' => $request->all()]);
            return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500); // إظهار رسالة خطأ أو رسالة عامة
        }
    }

    // ... باقي دوال الـ Controller ...

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate(['payment_status' => 'required|string']); // تأكد من أن الـ validation صحيح
        $order->update(['payment_status' => $request->payment_status]);
        $order->save();
        return response()->json(['message' => 'Update Successful']); // تغيير رسالة الخطأ لتكون أوضح
    }

    public function show($id)
    {
        $order = Order::with('services')->find($id); // استخدام with مباشرة مع find
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
        $orders = Order::with(['services', 'user'])->get();
        return response()->json(['orders' => $orders]);
    }
}