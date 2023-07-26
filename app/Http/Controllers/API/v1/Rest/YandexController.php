<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\Yandex\YandexService;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use Illuminate\Http\Request;
use Log;

class YandexController extends Controller
{
    use ApiResponse, Notification;

    public function __construct(private YandexService $service)
    {
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return void
     */
    public function webhook(Request $request): void
    {
//        $translations = [
//            'new' => 'Новая заявка.',
//            'estimating' => 'Идет процесс оценки заявки.',
//            'estimating_failed' => 'Не удалось оценить заявку',
//            'ready_for_approval' => 'Заявка успешно оценена и ожидает подтверждения от клиента.',
//            'accepted' => 'Заявка подтверждена клиентом.',
//            'performer_lookup' => 'Заявка взята в обработку. Промежуточный статус перед созданием заказа.',
//            'performer_draft' => 'Идет поиск водителя.',
//            'performer_found' => 'Водитель найден и едет в точку А.',
//            'performer_not_found' => 'Не удалось найти водителя. Можно попробовать снова через некоторое время.',
//            'pickup_arrived' => 'Водитель приехал в точку А.',
//            'ready_for_pickup_confirmation' => 'Водитель ждет, когда отправитель назовет ему код подтверждения.',
//            'pickuped' => 'Водитель успешно забрал груз.',
//            'pay_waiting' => 'Заказ ожидает оплаты (актуально для оплаты при получении).',
//            'delivery_arrived' => 'Водитель приехал в точку Б.',
//            'ready_for_delivery_confirmation' => 'Водитель ждет, когда получатель назовет ему код подтверждения.',
//            'delivered' => 'Водитель успешно доставил груз.',
//            'delivered_finish' => 'Заказ завершен.',
//            'returning' => 'Водителю пришлось вернуть груз и он едет в точку возврата.',
//            'return_arrived' => 'Водитель приехал в точку возврата.',
//            'ready_for_return_confirmation' => 'Водитель в точке возврата ожидает, когда ему назовут код подтверждения.',
//            'returned' => 'Водитель успешно вернул груз.',
//            'returned_finish' => 'Заказ завершен.',
//            'cancelled' => 'Заказ был отменен клиентом бесплатно.',
//            'cancelled_with_payment' => 'Заказ был отменен клиентом платно (водитель уже приехал).',
//            'cancelled_by_taxi' => 'Водитель отменил заказ (до получения груза).',
//            'cancelled_with_items_on_hands' => 'Клиент платно отменил заявку без необходимости возврата груза (заявка была создана с флагом optional_return).',
//            'failed' => 'При выполнение заказа произошла ошибка, дальнейшее выполнение невозможно.',
//        ];

        /** @var Order $order */
        $order = Order::whereJsonContains('yandex->id', $request->input('claim_id'))->first();

        if (empty($order)) {
            Log::error('empty yandex', $request->all());
            return;
        }

        $yandex = $order->yandex;
        $yandex['status'] = $request->input('status');

        $order->update([
            'yandex' => $yandex
        ]);

        if ($request->input('status') === 'ready_for_approval' && $order->status !== Order::STATUS_CANCELED) {
            (new YandexService)->acceptOrder($order);
        }

        if ($request->input('status') === 'performer_found' && $order->status !== Order::STATUS_CANCELED) {
            (new YandexService)->orderDriverVoiceForwarding($order);
        }

        if ($request->input('status') === 'pickuped' && $order->status !== Order::STATUS_CANCELED) {
            $order->update([
                'status' => Order::STATUS_ON_A_WAY
            ]);
        }

        if ($request->input('status') === 'delivered' && $order->status !== Order::STATUS_CANCELED) {
            $order->update([
                'status' => Order::STATUS_DELIVERED
            ]);
        }

//        $tStatus = data_get($translations, $request->input('status'));
//
//        if ($tStatus) {
//            $this->sendNotification(
//                $order->shop->seller->firebase_token,
//                "В заказе #$order->id, яндекс статус изменен на $tStatus",
//                "В заказе #$order->id, яндекс статус изменен на $tStatus"
//            );
//        }
//
//        $admins = User::with([
//            'roles' => fn($q) => $q->where('name', 'admin')
//        ])
//            ->whereHas('roles', fn($q) => $q->where('name', 'admin') )
//            ->whereNotNull('firebase_token')
//            ->pluck('firebase_token', 'id')
//            ->toArray();
//
//        $aTokens = [];
//
//        foreach ($admins as $adminToken) {
//            $aTokens = array_merge($aTokens, is_array($adminToken) ? array_values($adminToken) : [$adminToken]);
//        }
//
//        if ($tStatus) {
//            $this->sendNotification(
//                array_values(array_unique($aTokens)),
//                "В заказе #$order->id, яндекс статус изменен на $tStatus",
//                "В заказе #$order->id, яндекс статус изменен на $tStatus"
//            );
//        }

        Log::error('yandex', $request->all());
    }

}
