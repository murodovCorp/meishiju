<?php

namespace App\Imports;

use App\Models\ParcelOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ParcelOrderImport extends BaseImport implements ToCollection, WithHeadingRow, WithBatchInserts
{
    use Importable;

    public function __construct(private string $language) {}

    /**
     * @param Collection $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {

        foreach ($collection as $row) {

            ParcelOrder::updateOrCreate([
                'user_id'        => data_get($row,'user_id'),
                'type'           => data_get($row, 'type'),
                'username_from'  => data_get($row,'username_from'),
                'phone_from'     => data_get($row,'phone_from'),
                'username_to'    => data_get($row,'username_to'),
                'phone_to'       => data_get($row,'phone_to'),
                'total_price'    => data_get($row,'total_price'),
                'currency_id'    => data_get($row,'currency_id'),
                'rate'           => data_get($row,'rate'),
                'note'           => data_get($row,'note'),
                'km'             => data_get($row,'km'),
                'img'            => data_get($row,'img'),
                'tax'            => data_get($row,'tax') > 0 ? data_get($row,'tax') : 0,
                'status'         => data_get($row,'status'),
                'delivery_fee'   => data_get($row,'delivery_fee') > 0 ? data_get($row,'delivery_fee') : 0,
                'deliveryman_id' => data_get($row,'deliveryman_id'),
                'delivery_date'  => data_get($row,'delivery_date'),
                'delivery_time'  => data_get($row,'delivery_time'),
                'address_from'   => data_get($row, 'address_from', ''),
                'address_to'     => data_get($row, 'address_to', ''),
            ]);

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
