<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZddkApiService; // استيراد ZddkApiService
use App\Models\Order; // استيراد موديل الطلب الخاص بك
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // لاستخدام الـ Transactions

class CheckZddkOrderStatus extends Command
{
    /**
     * اسم وتوقيع الأمر الذي سيتم استخدامه في الـ Terminal.
     * @var string
     */
    protected $signature = 'zddk:check-orders-status';

    /**
     * وصف الأمر.
     * @var string
     */
    protected $description = 'Checks the status of pending ZDDK orders and updates them in the local database.';

    protected $zddkApi;

    /**
     * Constructor لحقن ZddkApiService.
     * @param ZddkApiService $zddkApi
     */
    public function __construct(ZddkApiService $zddkApi)
    {
        parent::__construct();
        $this->zddkApi = $zddkApi;
    }

    /**
     * تنفيذ الأمر.
     * @return int
     */
    public function handle()
    {
        $this->info('Starting ZDDK order status check...');

        // 1. جلب الطلبات المعلقة من قاعدة بياناتك المحلية
        // نحن نبحث عن الطلبات التي تم إرسالها إلى ZDDK API ولم يتم تأكيد حالتها بعد.
        // يجب أن تكون لها 'zddk_order_id' و 'zddk_status' ليست 'OK' أو 'CANCELLED'.
        $pendingLocalOrders = Order::whereNotNull('zddk_order_id')
                                   ->whereIn('zddk_status', ['wait', 'pending', 'processing']) // الحالات التي تنتظر التحديث من ZDDK
                                   ->get();

        if ($pendingLocalOrders->isEmpty()) {
            $this->info('No pending ZDDK orders found to check.');
            return Command::SUCCESS;
        }

        // جمع معرفات طلبات ZDDK لإرسالها في طلب واحد
        $zddkOrderIds = $pendingLocalOrders->pluck('zddk_order_id')->toArray();

        // 2. التحقق من حالة الطلبات مع ZDDK API
        $zddkResponse = $this->zddkApi->checkOrderStatus($zddkOrderIds);

        // التحقق من الاستجابة من ZDDK API
        if (!$zddkResponse || !isset($zddkResponse['status']) || $zddkResponse['status'] !== 'OK') {
            $this->error('Failed to get order statuses from ZDDK API or invalid response.');
            Log::error('ZDDK Order Status Check Error', ['response' => $zddkResponse, 'pending_orders' => $zddkOrderIds]);
            return Command::FAILURE;
        }

        // 3. تحديث حالة الطلبات في قاعدة بياناتك المحلية
        foreach ($zddkResponse['data'] as $zddkOrderStatus) {
            $zddkOrderId = $zddkOrderStatus['order_id'];
            $newStatus = $zddkOrderStatus['status'];
            $deliveryData = $zddkOrderStatus['replay_api'] ?? null; // بيانات التسليم مثل الإيميل والباسورد

            // البحث عن الطلب في قاعدة بياناتك المحلية
            $localOrder = $pendingLocalOrders->where('zddk_order_id', $zddkOrderId)->first();

            if ($localOrder) {
                // التأكد من أن الحالة الجديدة مختلفة قبل التحديث والحفظ
                if ($localOrder->zddk_status !== $newStatus) {
                    DB::beginTransaction(); // استخدام Transaction لحماية التحديثات
                    try {
                        $localOrder->zddk_status = $newStatus;

                        if ($newStatus === 'OK') {
                            $localOrder->payment_status = 'completed'; // تحديث حالة الدفع المحلية إلى مكتمل
                            $localOrder->zddk_delivery_data = json_encode($deliveryData); // حفظ بيانات التسليم
                            $this->info("Order {$zddkOrderId} (Local ID: {$localOrder->id}) updated to COMPLETED.");
                            // هنا يمكنك إضافة منطق لإرسال الخدمة للعميل (إيميل، رسالة، تحديث واجهة المستخدم)
                        } elseif ($newStatus === 'CANCELLED') {
                            $localOrder->payment_status = 'failed'; // تحديث حالة الدفع المحلية إلى فاشل
                            // يمكنك إضافة منطق لاسترجاع المبلغ للمستخدم هنا إذا كان قد دفعه
                            $this->warn("Order {$zddkOrderId} (Local ID: {$localOrder->id}) updated to CANCELLED. Consider refunding.");
                        } else {
                            // حالات أخرى مثل 'wait' أو 'processing'
                            $this->info("Order {$zddkOrderId} (Local ID: {$localOrder->id}) status remains '{$newStatus}'.");
                        }
                        $localOrder->save();
                        DB::commit(); // تأكيد الـ Transaction
                    } catch (\Exception $e) {
                        DB::rollBack(); // التراجع في حالة حدوث خطأ
                        Log::error("Failed to update local order status for ZDDK Order ID: {$zddkOrderId}. Error: " . $e->getMessage());
                        $this->error("Error updating order {$zddkOrderId}. Check logs.");
                    }
                } else {
                    $this->info("Order {$zddkOrderId} (Local ID: {$localOrder->id}) status is already '{$newStatus}'. No update needed.");
                }
            } else {
                $this->warn("ZDDK Order ID {$zddkOrderId} not found in local database, but returned by API.");
            }
        }

        $this->info('ZDDK order status check completed.');
        return Command::SUCCESS;
    }
}