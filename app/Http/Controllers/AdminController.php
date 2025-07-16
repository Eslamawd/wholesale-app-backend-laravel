<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User; // تأكد من استخدام موديل User الصحيح
use Illuminate\Http\Request;
use App\Services\ZddkApiService; // استيراد ZddkApiService
use Illuminate\Support\Facades\Log; // لاستخدام Log::error

class AdminController extends Controller
{
    

    /**
     * زيادة رصيد المستخدم المحدد.
     * يستخدم دالة deposit() من حزمة Bavix\Wallet.
     *
     * @param Request $request
     * @param int $id معرف المستخدم
     * @return \Illuminate\Http\JsonResponse
     */
    public function recharge(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = User::findOrFail($id);

        // استخدام دالة deposit() من حزمة Bavix\Wallet لزيادة الرصيد
        $user->deposit($request->amount);

        // لا حاجة لـ $user->wallet_balance = $user->balance; هنا، الحزمة تقوم بذلك تلقائياً

        return response()->json([
            'message' => 'User balance recharged successfully',
            'balance' => $user->balance // سيقوم بعرض الرصيد المحدث
        ]);
    }

    /**
     * جلب جميع الطلبات مع تفاصيل المستخدم والخدمات.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function allOrders()
    {
        $orders = Order::with(['user', 'services'])->latest()->get();
        return response()->json($orders);
    }

    /**
     * جلب تفاصيل الطلب المحدد.
     *
     * @param int $id معرف الطلب
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderDetails($id)
    {
        $order = Order::with(['user', 'services'])->findOrFail($id);
        return response()->json($order);
    }

    /**
     * جلب جميع المستخدمين.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllUsers()
    {
        // استخدام latest() مباشرة على Query Builder
        $users = User::latest()->get();
        return response()->json($users);
    }

    /**
     * حذف الطلب المحدد.
     *
     * @param int $id معرف الطلب
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOrder($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }

    /**
     * تحديث حالة الطلب المحدد.
     *
     * @param Request $request
     * @param int $id معرف الطلب
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string', // افترض أنك ترسل الحالة في 'status'
        ]);

        $order = Order::findOrFail($id);
        $order->update(['payment_status' => $request->status]);
        return response()->json(['message' => 'Order status updated successfully', 'status' => $order->payment_status]);
    }

}