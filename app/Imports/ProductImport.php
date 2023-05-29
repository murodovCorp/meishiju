<?php

namespace App\Imports;

use App\Models\Language;
use App\Models\Product;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Concerns\ShouldQueueWithoutChain;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ProductImport extends BaseImport implements ToCollection, WithHeadingRow, WithBatchInserts, ShouldQueueWithoutChain, WithChunkReading
{
    use Importable, Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(private ?int $shopId, private string $language) {}

    /**
     * @param Collection $collection
     * @return void
     * @throws Throwable
     */
    public function collection(Collection $collection): void
    {
        $language = Language::where('default', 1)->first();

        foreach ($collection as $row) {

            DB::transaction(function () use ($row, $language) {

                $data = [
                    'shop_id'       => $this->shopId ?? data_get($row,'shop_id'),
                    'category_id'   => data_get($row, 'category_id'),
                    'brand_id'      => data_get($row, 'brand_id'),
                    'unit_id'       => data_get($row, 'unit_id'),
                    'keywords'      => data_get($row, 'keywords', ''),
                    'tax'           => data_get($row, 'tax', 0),
                    'active'        => data_get($row, 'active') === 'active' ? 1 : 0,
                    'img'           => data_get($row, 'img'),
                    'bar_code'      => data_get($row, 'bar_code', ''),
                    'qr_code'       => data_get($row, 'qr_code', ''),
                    'status'        => in_array(data_get($row, 'status'), Product::STATUSES) ? data_get($row, 'status') : Product::PENDING,
                    'min_qty'       => data_get($row, 'min_qty', 1),
                    'max_qty'       => data_get($row, 'max_qty', 1000000),
                    'addon'         => data_get($row, 'addon'),
                    'vegetarian'    => data_get($row, 'vegetarian'),
                    'kcal'          => data_get($row, 'kcal'),
                    'carbs'         => data_get($row, 'carbs'),
                    'protein'       => data_get($row, 'protein'),
                    'fats'          => data_get($row, 'fats'),
                ];

                $product = Product::withTrashed()->updateOrCreate($data, $data + [
                    'deleted_at' => null
                ]);

                $this->downloadImages($product, data_get($row, 'img_urls', ''));

                if (!empty(data_get($row, 'product_title'))) {
                    $product->translation()->updateOrInsert([
                        'product_id'    => $product->id,
                        'locale'        => $this->language ?? $language,
                    ], [
                        'title'         => data_get($row, 'product_title', ''),
                        'description'   => data_get($row, 'product_description', '')
                    ]);
                }

                if (!empty(data_get($row, 'price')) || !empty(data_get($row, 'quantity'))) {
                    $product->stocks()->updateOrInsert([
                        'countable_type'  => get_class($product),
                        'countable_id'  => $product->id,
                    ], [
                        'price'     => data_get($row, 'price')    > 0 ? data_get($row, 'price') : 0,
                        'quantity'  => data_get($row, 'quantity') > 0 ? data_get($row, 'quantity') : 0,
                    ]);
                }
            });

        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }

}
