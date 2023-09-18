<?php

namespace App\Observers;

use App\Models\Stock;
use App\Services\DeletingService\DeletingService;
use App\Services\ModelLogService\ModelLogService;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class StockObserver
{
    use Loggable;

    /**
     * Handle the Stock "created" event.
     *
     * @param Stock $stock
     * @return void
     */
    public function created(Stock $stock): void
    {
        (new ModelLogService)->logging($stock, $stock->getAttributes(), 'created');
    }

    /**
     * Handle the Stock "updated" event.
     *
     * @param Stock $stock
     * @return void
     */
    public function updated(Stock $stock): void
    {
		$stock->cartDetails()->delete();

        (new ModelLogService)->logging($stock, $stock->getAttributes(), 'updated');
    }

	/**
	 * Handle the Stock "deleted" event.
	 *
	 * @param Stock $stock
	 * @return void
	 */
    public function deleted(Stock $stock): void
    {
        (new DeletingService)->stock($stock);

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($stock, $stock->getAttributes(), 'deleted');
    }

    /**
     * Handle the Stock "restored" event.
     *
     * @param Stock $stock
     * @return void
     */
    public function restored(Stock $stock): void
    {
        (new ModelLogService)->logging($stock, $stock->getAttributes(), 'restored');
    }
}
