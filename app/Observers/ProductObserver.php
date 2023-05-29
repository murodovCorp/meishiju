<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\DeletingService\DeletingService;
use App\Services\ModelLogService\ModelLogService;
use App\Traits\Loggable;
use Cache;
use Illuminate\Support\Str;

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
        Cache::flush();

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
        Cache::flush();

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
        (new DeletingService)->product($product);
        Cache::flush();

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
