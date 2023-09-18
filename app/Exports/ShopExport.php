<?php

namespace App\Exports;

use App\Models\Shop;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShopExport extends BaseExport implements FromCollection, WithHeadings
{
    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $shops = Shop::orderBy('id')->get();

        return $shops->map(fn (Shop $shop) => $this->tableBody($shop));
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Id',
            'Uu Id',
            'User Id',
            'Tax',
            'Percentage',
            'Location',
            'Phone',
            'Show Type',
            'Open',
            'Img Urls',
            'Min Amount',
            'Status',
            'Status Note',
            'Created At',
            'Delivery Time',
            'Delivery Price',
            'Delivery Price Per Km',
        ];
    }

    /**
     * @param Shop $shop
     * @return array
     */
    private function tableBody(Shop $shop): array
    {

        $from = data_get($shop->delivery_time, 'from', '');
        $to   = data_get($shop->delivery_time, 'to', '');
        $type = data_get($shop->delivery_time, 'type', '');

        return [
            'id'                => $shop->id,
            'uuid'              => $shop->uuid,
            'user_id'           => $shop->user_id,
            'tax'               => $shop->tax,
            'percentage'        => $shop->percentage,
            'location'          => implode(',', $shop->location),
            'phone'             => $shop->phone,
            'show_type'         => $shop->show_type,
            'open'              => $shop->open,
            'img_urls'          => $this->imageUrl($shop->galleries),
            'min_amount'        => $shop->min_amount,
            'status'            => $shop->status,
            'status_note'       => $shop->status_note,
            'created_at'        => $shop->created_at ?? date('Y-m-d H:i:s'),
            'delivery_time'     => "from: $from, to: $to, type: $type",
            'delivery_price'    => $shop->delivery_price,
        ];
    }
}
