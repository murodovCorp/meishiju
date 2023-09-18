<?php

namespace App\Observers;

use App\Models\Shop;
use App\Services\DeletingService\DeletingService;
use App\Services\ModelLogService\ModelLogService;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Cache;
use Exception;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class ShopObserver
{
    use Loggable;

    /**
     * Handle the Shop "creating" event.
     *
     * @param Shop $shop
     * @return void
     */
    public function creating(Shop $shop): void
    {

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}

        $shop->uuid = Str::uuid();
    }

    /**
     * Handle the Shop "created" event.
     *
     * @param Shop $shop
     * @return void
     */
    public function created(Shop $shop): void
    {
        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($shop, $shop->getAttributes(), 'created');
    }

    /**
     * Handle the Shop "updated" event.
     *
     * @param Shop $shop
     * @return void
     */
    public function updated(Shop $shop): void
    {

        if ($shop->status == 'approved') {

            if (!$shop->seller->hasRole('admin')) {
                $shop->seller?->syncRoles('seller');
            }

            $shop->seller?->invitations()?->delete();
        }

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($shop, $shop->getAttributes(), 'updated');
    }

    /**
     * Handle the Shop "deleted" event.
     *
     * @param Shop $shop
     * @return void
     */
    public function deleted(Shop $shop): void
    {
        (new DeletingService)->shop($shop);

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($shop, $shop->getAttributes(), 'deleted');
    }

    /**
     * Handle the Shop "restored" event.
     *
     * @param Shop $shop
     * @return void
     */
    public function restored(Shop $shop): void
    {
        (new ModelLogService)->logging($shop, $shop->getAttributes(), 'restored');
    }

}
