<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str; // تأكد من استيراد كلاس Str لاستخدام Str::uuid()

class ZddkApiService
{
    protected $baseUrl;
    protected $apiToken;

    public function __construct()
    {
        $this->baseUrl = env('ZDDK_BASE_URL' );
        $this->apiToken = env('ZDDK_API_TOKEN');
    }

    /**
     * دالة مساعدة لإرسال الطلبات إلى ZDDK API
     * @param string $method نوع الطلب (get, post)
     * @param string $endpoint المسار النسبي لنقطة النهاية (مثال: 'products')
     * @param array $params البارامترات المراد إرسالها مع الطلب (Query Parameters for GET)
     * @return array|null استجابة الـ API في شكل مصفوفة PHP أو null عند الخطأ
     */
    protected function callApi($method, $endpoint, $params = [])
    {
        try {
            $response = Http::withHeaders([
                'api-token' => $this->apiToken,
            ])
            ->timeout(30) // تعيين مهلة للطلب (بالثواني)
            ->$method($this->baseUrl . $endpoint, $params);

            if ($response->successful()) {
                return $response->json();
            } else {
                \Log::error("ZDDK API Error: " . $response->status() . " - " . $response->body(), [
                    'endpoint' => $endpoint,
                    'params' => $params
                ]);
                return null;
            }
        } catch (\Exception $e) {
            \Log::error("ZDDK API Connection Error: " . $e->getMessage(), [
                'endpoint' => $endpoint,
                'params' => $params
            ]);
            return null;
        }
    }

    /**
     * جلب قائمة المنتجات من ZDDK API
     * @param array $productIds (اختياري) لفلترة المنتجات حسب ID
     * @return array|null قائمة المنتجات أو null عند الخطأ
     */
    public function getProducts(array $productIds = [])
    {
        $params = [];
        if (!empty($productIds)) {
            $params['products_id'] = $productIds;
        }
        return $this->callApi('get', 'products', $params);
    }

    /**
     * جلب رصيد الحساب من ZDDK API (الملف الشخصي)
     * @return array|null بيانات الملف الشخصي التي تحتوي على الرصيد أو null عند الخطأ
     */
    public function getBalance()
    {
        return $this->callApi('get', 'profile');
    }

    /**
     * إنشاء طلب جديد عبر ZDDK API
     * @param int $productId معرف المنتج في ZDDK API
     * @param int $qty الكمية المطلوبة من المنتج
     * @param array $extraParams بارامترات إضافية خاصة بالخدمة (مثل playerId, username, phone_number, etc.)
     * @param string|null $orderUuid معرف فريد لطلبك (UUID) لمنع التكرار، إذا لم يتم توفيره سيتم توليده
     * @return array|null استجابة إنشاء الطلب أو null عند الخطأ
     */
    public function createOrder(int $productId, int $qty, array $extraParams = [], ?string $orderUuid = null)
    {
        // توليد UUID إذا لم يتم توفيره (مهم جداً لمنع تكرار الطلبات)
        if (is_null($orderUuid)) {
            $orderUuid = (string) Str::uuid();
        }

        $queryParams = array_merge([
            'qty' => $qty,
            'order_uuid' => $orderUuid,
        ], $extraParams);

        // بناء الـ endpoint بالشكل newOrder/{productId}/params
        // الـ API يتوقع البارامترات في الـ query string
        $endpoint = "newOrder/{$productId}/params";
        return $this->callApi('get', $endpoint, $queryParams); // ZDDK API يستخدم GET لإنشاء الطلبات
    }

    /**
     * التحقق من حالة الطلبات في ZDDK API
     * @param array $orderIds مصفوفة بمعرفات الطلبات الخاصة بـ ZDDK (ZDDK's order_id) المراد التحقق منها
     * @return array|null حالة الطلبات أو null عند الخطأ
     */
    public function checkOrderStatus(array $orderIds)
    {
        // الـ API يتوقع orders كـ string مثل '[ID_1,ID_2]' في الـ query params
        $ordersString = '[' . implode(',', $orderIds) . ']';
        return $this->callApi('get', 'check', ['orders' => $ordersString]);
    }

    /**
     * جلب الفئات من ZDDK API
     * @param int $parentId (اختياري) ID الفئة الأب لجلب الفئات الفرعية
     * @return array|null قائمة الفئات أو null عند الخطأ
     */
    public function getCategories(int $parentId = 0)
    {
        return $this->callApi('get', "content/{$parentId}");
    }
}