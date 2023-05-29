<?php

namespace App\Observers;

use App\Jobs\AttachDeliveryMan;
use App\Models\Language;
use App\Models\Order;
use App\Models\Settings;
use App\Services\ModelLogService\ModelLogService;
use App\Traits\Notification;

class OrderObserver
{
    use Notification;

    /**
     * Handle the Brand "created" event.
     *
     * @param Order $order
     * @return void
     */
    public function created(Order $order): void
    {
        if ($order->status === Order::STATUS_ACCEPTED && empty($order->deliveryman) && $this->autoDeliveryMan()) {
            AttachDeliveryMan::dispatchAfterResponse($order, $this->language());
        }

        (new ModelLogService)->logging($order, $order->getAttributes(), 'created');
    }

    /**
     * Handle the Brand "updated" event.
     *
     * @param Order $order
     * @return void
     */
    public function updated(Order $order): void
    {
        if ($order->status === Order::STATUS_ACCEPTED && empty($order->deliveryman) && $this->autoDeliveryMan()) {
            AttachDeliveryMan::dispatchAfterResponse($order, $this->language());
        }

        (new ModelLogService)->logging($order, $order->getAttributes(), 'updated');
    }

    /**
     * Handle the Order "restored" event.
     *
     * @param Order $order
     * @return void
     */
    public function deleted(Order $order): void
    {
        (new ModelLogService)->logging($order, $order->getAttributes(), 'deleted');
    }

    /**
     * Handle the Order "restored" event.
     *
     * @param Order $order
     * @return void
     */
    public function restored(Order $order): void
    {
        (new ModelLogService)->logging($order, $order->getAttributes(), 'restored');
    }

    /**
     * @return string
     */
    public function language(): string
    {
        return request(
            'lang',
            data_get(Language::where('default', 1)->first(['locale', 'default']), 'locale')
        );
    }

    /**
     * @return bool
     */
    public function autoDeliveryMan(): bool
    {
        $autoDeliveryMan = Settings::adminSettings()->where('key', 'order_auto_delivery_man')->first();

        return (int)data_get($autoDeliveryMan, 'value', 0) === 1;
    }

}
