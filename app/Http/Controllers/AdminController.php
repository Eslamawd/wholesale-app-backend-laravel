<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    //
       public function recharge(Request $request,  $id)
    {
        $user = \App\Models\User::findOrFail($id);

        // Validate the request
        // Recharge user balance
        $user->balance += $request->amount;
        $user->save();

        
        return response()->json(['message' => 'User balance recharged successfully', 'balance' => $user->balance]);
    }

    public function allOrders()
{
    // جلب جميع الطلبات مع تفاصيل المستخدم والخدمات
    $orders = Order::with(['user', 'services'])->latest()->get();
    return response()->json($orders);
}
    public function orderDetails($id)
    {
        // جلب تفاصيل الطلب المحدد
        $order = Order::with(['user', 'services'])->findOrFail($id);
        return response()->json($order);
    }

public function getAllUsers()
{
    // جلب جميع المستخدمين
    $users = User::all()->latest()->get();
    return response()->json($users);
}
    public function deleteOrder($id)
    {
        // حذف الطلب المحدد
        $order = Order::findOrFail($id);
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
    public function updateOrderStatus(Request $request, $id)
    {
        // تحديث حالة الطلب
        $order = Order::findOrFail($id);
        $order->update(['payment_status' => $request->status]);
        return response()->json(['message' => 'Order status updated successfully', 'status' => $order->payment_status]);
    }


}
