<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\DeletingService\DeletingService;
use App\Services\ModelLogService\ModelLogService;
use App\Traits\Loggable;
use DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class ProductObserver
{
    use Loggable;
    /**
     * Handle the Product "creating" event.
     *
     * @param Product $product
     * @return void
     */
    public function creating(Product $product): void
    {
        $product->uuid = Str::uuid();
    }

	/**
	 * Handle the Product "created" event.
	 *
	 * @param Product $product
	 * @return void
	 */
    public function created(Product $product): void
    {
        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($product, $product->getAttributes(), 'created');
    }

	/**
	 * Handle the Product "updated" event.
	 *
	 * @param Product $product
	 * @return void
	 */
    public function updated(Product $product): void
    {
        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

		$product = $product->loadMissing(['stocks.bonus', 'stocks.cartDetails']);

		foreach ($product->stocks as $stock) {
			DB::table('cart_details')->where('stock_id', $stock->bonus?->bonus_stock_id)->delete();
			$stock->cartDetails()->delete();
		}

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($product, $product->getAttributes(), 'updated');
    }

	/**
	 * Handle the Product "deleted" event.
	 *
	 * @param Product $product
	 * @return void
	 */
    public function deleted(Product $product): void
    {
        (new DeletingService)->product($product->load([
            'stocks.cartDetails',
            'stocks.bonus',
            'stocks.bonusByShop',
            'stocks.addons',
            'stories',
            'addons'
        ]));

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($product, $product->getAttributes(), 'deleted');
    }

    /**
     * Handle the Product "restored" event.
     *
     * @param Product $product
     * @return void
     */
    public function restored(Product $product): void
    {
        (new ModelLogService)->logging($product, $product->getAttributes(), 'restored');
    }

}
