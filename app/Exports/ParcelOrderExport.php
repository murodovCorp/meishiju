<?php

namespace App\Exports;

use App\Models\ParcelOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ParcelOrderExport extends BaseExport implements FromCollection, WithHeadings
{
    protected array $filter;

    public function __construct(array $filter)
    {
        $this->filter = $filter;
    }

    public function collection(): Collection
    {
        $parcelOrders = ParcelOrder::filter($this->filter)
            ->with([
                'user:id,firstname',
                'deliveryman:id,firstname',
            ])
            ->orderBy('id')
            ->get();

        return $parcelOrders->map(fn(ParcelOrder $parcelOrder) => $this->tableBody($parcelOrder));
    }

    public function headings(): array
    {
        return [
            'Id',
            'User Id',
            'Type',
            'Customer',
            'Username',
            'Customer phone',
            'Username phone',
            'Total Price',
            'Currency Id',
            'Currency Title',
            'Rate',
            'Note',
            'Tax',
            'Status',
            'Delivery Fee',
            'Deliveryman',
            'Deliveryman Name',
            'Delivery Date',
            'Delivery Time',
            'Address Customer',
            'Address Username',
            'Created At',
        ];
    }

    private function tableBody(ParcelOrder $parcelOrder): array
    {
        $currencyTitle  = data_get($parcelOrder->currency, 'title');
        $currencySymbol = data_get($parcelOrder->currency, 'symbol');

        return [
           'id'                 => $parcelOrder->id,
           'user_id'            => $parcelOrder->user_id,
           'type'               => $parcelOrder->type,
           'username_from'      => $parcelOrder->username_from ?? "{$parcelOrder->user?->firstname} {$parcelOrder->user?->lastname}",
           'username_to'        => $parcelOrder->username_to,
           'phone_from'         => $parcelOrder->phone_from,
           'phone_to'           => $parcelOrder->phone_to,
           'total_price'        => $parcelOrder->total_price,
           'currency_id'        => $parcelOrder->currency_id,
           'currency_title'     => "$currencyTitle($currencySymbol)",
           'rate'               => $parcelOrder->rate,
           'note'               => $parcelOrder->note,
           'tax'                => $parcelOrder->tax,
           'status'             => $parcelOrder->status,
           'delivery_fee'       => $parcelOrder->delivery_fee,
           'deliveryman_id'     => $parcelOrder->deliveryman_id,
           'deliveryman_name'   => "{$parcelOrder->deliveryman?->firstname} {$parcelOrder->deliveryman?->lastname}",
           'delivery_date'      => $parcelOrder->delivery_date,
           'delivery_time'      => $parcelOrder->delivery_time,
           'address_from'       => $parcelOrder->address_from,
           'address_to'         => $parcelOrder->address_to,
           'created_at'         => $parcelOrder->created_at ?? date('Y-m-d H:i:s'),
        ];
    }
}
