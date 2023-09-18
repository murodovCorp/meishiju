<?php

namespace App\Exports;

use App\Models\Language;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderExport extends BaseExport implements FromCollection, WithHeadings
{
    protected array $filter;

    public function __construct(array $filter)
    {
        $this->filter = $filter;
    }

    public function collection(): Collection
    {
        $language = Language::where('default', 1)->first();

        $orders = Order::filter($this->filter)
            ->with([
                'user:id,firstname',
                'shop:id',
                'shop.translation'  => fn($q) => $q->select('locale', 'title', 'shop_id')
                    ->where('locale', data_get($this->filter, 'language'))
                    ->orWhere('locale', $language),

                'deliveryMan:id,firstname',
            ])
            ->orderBy('id')
            ->get();

        return $orders->map(fn(Order $order) => $this->tableBody($order));
    }

    public function headings(): array
    {
        return [
            'Id',
            'User Id',
            'Username',
            'Total Price',
            'Currency Id',
            'Currency Title',
            'Rate',
            'Note',
            'Shop Id',
            'Shop Title',
            'Tax',
            'Commission Fee',
            'Status',
            'Delivery Fee',
            'Deliveryman',
            'Deliveryman Firstname',
            'Delivery Date',
            'Delivery Time',
            'Total Discount',
            'Location',
            'Address',
            'Delivery Type',
            'Phone',
            'Created At',
        ];
    }

    private function tableBody(Order $order): array
    {
        $currencyTitle  = data_get($order->currency, 'title');
        $currencySymbol = data_get($order->currency, 'symbol');

        return [
           'id'                     => $order->id,
           'user_id'                => $order->user_id,
           'username'               => $order->username ?? optional($order->user)->firstname,
           'total_price'            => $order->total_price,
           'currency_id'            => $order->currency_id,
           'currency_title'         => "$currencyTitle($currencySymbol)",
           'rate'                   => $order->rate,
           'note'                   => $order->note,
           'shop_id'                => $order->shop_id,
           'shop_title'             => data_get(optional($order->shop)->translation, 'title'),
           'tax'                    => $order->tax,
           'commission_fee'         => $order->commission_fee,
           'status'                 => $order->status,
           'delivery_fee'           => $order->delivery_fee,
           'deliveryman'            => $order->deliveryman,
           'deliveryman_firstname'  => optional($order->deliveryMan)->firstname,
           'delivery_date'          => $order->delivery_date,
           'delivery_time'          => $order->delivery_time,
           'total_discount'         => $order->total_discount,
           'location'               => is_array($order->location) ? implode(',', $order->location) : $order->location,
           'address'                => $order->address,
           'delivery_type'          => $order->delivery_type,
           'phone'                  => $order->phone,
           'created_at'             => $order->created_at ?? date('Y-m-d H:i:s'),
        ];
    }
}
