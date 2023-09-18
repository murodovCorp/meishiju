<?php

namespace App\Traits;

use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\Product;
use App\Models\Stock;
use App\Services\ProductService\ProductService;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * @property Collection|Stock[] $stocks
 * @property int|null $stocks_count
*/
trait Countable
{
    /**
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function addInStock(array $data): void
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
        $lang   = request('lang', $locale);

        try {
            DB::transaction(function () use ($data, $lang) {

                $extras = data_get($data, 'extras', []);

                if (data_get($data, 'delete_ids')) {
                    $this->stocks()->whereIn('id', data_get($data, 'delete_ids'))->delete();
                }

                $notDeleteIds = [];

                foreach ($extras as $i => $item) {

                    $ids = data_get($item, 'ids');

                    // when trying to add duplicate stock
                    foreach ($extras as $k => $extra) {

                        $duplicateIds = data_get($extra, 'ids', []);

                        if (
                            $i !== $k && is_array($ids)
                            && is_array($duplicateIds)
                            && empty(array_diff($ids, $duplicateIds))
                        ) {

                            throw new Exception(
                                __('errors.' . ResponseError::ERROR_119, locale: $lang),
                                119
                            );

                        }

                    }

                    if (data_get($item, 'stock_id')) {

                        $stock = Stock::find(data_get($item, 'stock_id'));

                        $stock->update([
                            'countable_type' => Product::class,
                            'price'          => data_get($item, 'price'),
                            'quantity'       => data_get($item, 'quantity'),
                            'sku'            => data_get($item, 'sku'),
                            'addon'          => $this->addon
                        ]);

                    } else if ($this->addon) {

                        $stock = $this->stocks()->updateOrCreate([
                            'countable_id'   => $this->id,
                            'countable_type' => Product::class,
                        ], [
                            'addon'          => true,
                            'price'          => data_get($item, 'price'),
                            'quantity'       => data_get($item, 'quantity'),
							'sku'            => data_get($item, 'sku'),
                        ]);

                    } else {

                        $stock = $this->stocks()->create([
                            'countable_id'   => $this->id,
                            'countable_type' => Product::class,
                            'addon'          => $this->addon,
                            'price'          => data_get($item, 'price'),
                            'quantity'       => data_get($item, 'quantity'),
                            'sku'            => data_get($item, 'sku'),
                        ]);

                    }

                    if (empty($ids)) {
                        DB::table('stock_extras')->where('stock_id', $stock->id)->delete();
                    }

                    if (empty(data_get($item, 'addons'))) {
                        $stock->addons()->delete();
                    }

                    if (is_array($ids)) {
                        $stock->stockExtras()->sync($ids);
                    }

                    if (is_array(data_get($item, 'addons')) && count(data_get($item, 'addons')) > 0) {
                        (new ProductService)->syncAddons($stock, data_get($item, 'addons'));
                    }

                    $notDeleteIds[] = $stock->id;
                }

                if (count($notDeleteIds) > 0) {
                    $this->fresh(['stocks'])->stocks()->whereNotIn('id', $notDeleteIds)->delete();
                }

            });
        } catch (Throwable $e) {
            throw new Exception(
				$e->getMessage() . ' | ' . $e->getFile() . ' | ' . $e->getLine(),
                400
            );
        }

    }

    /**
     * @return HasMany
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'countable_id');
    }

}
