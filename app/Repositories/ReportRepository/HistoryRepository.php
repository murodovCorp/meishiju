<?php

namespace App\Repositories\ReportRepository;

use App\Models\Order;
use App\Models\Transaction;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HistoryRepository
{
    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $filter['status'] = Order::STATUS_DELIVERED;

        return Order::filter($filter)
            ->with([
                'user:id,firstname,lastname',
                'transactions:id,payable_id,payable_type,status,payment_sys_id',
                'transactions.paymentSystem:id,tag'
            ])
            ->select([
                'id',
                'user_id',
                'total_price',
                'created_at',
                'note',
            ])
            ->when($filter['type'] === 'today', fn($query) =>
                $query->where('created_at', '>=', now()->format('Y-m-d 00:00:01'))
            )
            ->orderBy(data_get($filter, 'column', 'created_at'), data_get($filter, 'sort', 'asc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function cards(array $filter): array
    {
        $deliveryFee = DB::table('orders')
            ->where('created_at', '>=', now()->format('Y-m-d 00:00:01'))
            ->where('status', Order::STATUS_DELIVERED)
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->whereNull('deleted_at')
            ->select([
                DB::raw('sum(delivery_fee) as delivery_fee')
            ])
            ->first();

        $transactions = Transaction::with('paymentSystem:id,tag')
            ->select([
                'id',
                'price',
                'payment_sys_id',
            ])
            ->where('status', Transaction::STATUS_PAID)
            ->where('created_at', '>=', now()->format('Y-m-d 00:00:01'))
            ->get();

        $cash  = 0;
        $other = 0;

        foreach ($transactions as $transaction) {

            if ($transaction?->price && $transaction->paymentSystem?->tag === 'cash') {
                $cash += $transaction->price;
            }

            if ($transaction?->price && $transaction->paymentSystem?->tag !== 'cash') {
                $other += $transaction->price;
            }

        }

        return [
            'delivery_fee'  => $deliveryFee?->delivery_fee ?? 0,
            'cash'          => $cash,
            'other'         => $other,
        ];
    }

    public function mainCards(array $filter): array
    {
        $dateFrom   = $filter['date_from'];
        $dateTo     = $filter['date_to'];
        $days       = (round((strtotime($dateFrom) - strtotime($dateTo)) / (60 * 60 * 24)) + 1) * 2;

        $prevFrom = date('Y-m-d 00:00:01', strtotime("$dateFrom $days days"));

        $prevTo   = date('Y-m-d 23:59:59', strtotime($dateTo));

        $curFrom  = date('Y-m-d 00:00:01', strtotime($dateFrom));

        $prevPeriod = DB::table('orders')
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->where('created_at', '>=', $prevFrom)
            ->where('created_at', '<=', $curFrom)
            ->whereNull('deleted_at')
            ->select([
                DB::raw("sum(if(status = 'delivered', total_price, 0)) as revenue"),
                DB::raw('sum(total_price) as orders'),
                DB::raw("avg(if(status = 'delivered', total_price, 0)) as average"),
            ])
            ->first();

        $curPeriod = DB::table('orders')
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->where('created_at', '>=', $curFrom)
            ->where('created_at', '<=', $prevTo)
            ->whereNull('deleted_at')
            ->select([
                DB::raw("sum(if(status = 'delivered', total_price, 0)) as revenue"),
                DB::raw('sum(total_price) as orders'),
                DB::raw("avg(if(status = 'delivered', total_price, 0)) as average"),
            ])
            ->first();

        $revenue        = (int)data_get($curPeriod, 'revenue');
        $prevRevenue    = (int)data_get($prevPeriod, 'revenue');

        $orders         = (int)data_get($curPeriod, 'orders');
        $prevOrders     = (int)data_get($prevPeriod, 'orders');

        $average        = (int)data_get($curPeriod, 'average');
        $prevAverage    = (int)data_get($prevPeriod, 'average');

        $revenuePercent = $revenue - $prevRevenue;
        $revenuePercent = $revenuePercent > 1 && $prevRevenue > 1 ? $revenuePercent / $prevRevenue * 100 : 100;

        $ordersPercent  = $orders - $prevOrders;
        $ordersPercent  = $ordersPercent > 1 && $prevOrders > 1 ? $ordersPercent / $prevOrders * 100 : 100;

        $averagePercent = $average - $prevAverage;
        $averagePercent = $averagePercent > 1 && $prevAverage > 1 ? $averagePercent / $prevAverage * 100 : 100;

        return [
            'revenue'               => $revenue,
            'revenue_percent'       => $revenue <= 0 ? 0 : $revenuePercent,
            'revenue_percent_type'  => $revenuePercent <= 0 ? 'minus' : 'plus',
            'orders'                => $orders,
            'orders_percent'        => $orders <= 0 ? 0 : $ordersPercent,
            'orders_percent_type'   => $ordersPercent <= 0 ? 'minus' : 'plus',
            'average'               => $average,
            'average_percent'       => $average <= 0 ? 0 : $averagePercent,
            'average_percent_type'  => $averagePercent <= 0 ? 'minus' : 'plus',
        ];
    }

    public function chart(array $filter): Collection
    {
        $type     = data_get($filter, 'type', 'day');
        $dateFrom = date('Y-m-d 00:00:01', strtotime(request('date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(request('date_to', now())));

        $type = match ($type) {
            'year'  => '%Y',
            'week'  => '%Y-%m-%d %w',
            'month' => '%Y-%m-%d',
            'day'   => '%Y-%m-%d %H:00',
        };

        return DB::table('orders')
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->whereNull('deleted_at')
            ->where('status', Order::STATUS_DELIVERED)
            ->select([
                DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
                DB::raw('sum(total_price) as total_price'),
            ])
            ->groupBy('time')
            ->orderBy('time')
            ->get();
    }

    public function statistic(array $filter): array
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(request('date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(request('date_to', now())));

        $order = DB::table('orders')
            ->select([
                DB::raw("sum(if(status = 'new',       1, 0)) as new_total_count"),
                DB::raw("sum(if(status = 'accepted',  1, 0)) as accepted_total_count"),
                DB::raw("sum(if(status = 'ready',     1, 0)) as ready_total_count"),
                DB::raw("sum(if(status = 'on_a_way',  1, 0)) as on_a_way_total_count"),
                DB::raw("sum(if(status = 'delivered', 1, 0)) as delivered_total_count"),
                DB::raw("sum(if(status = 'canceled',  1, 0)) as canceled_total_count"),
            ])
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->whereNull('deleted_at')
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->first();

        $new                = (double)data_get($order, 'new_total_count');
        $accepted           = (double)data_get($order, 'accepted_total_count');
        $ready              = (double)data_get($order, 'ready_total_count');
        $onAWay             = (double)data_get($order, 'on_a_way_total_count');
        $delivered          = (double)data_get($order, 'delivered_total_count');
        $canceled           = (double)data_get($order, 'canceled_total_count');

        $totalPrice         = $new + $accepted + $ready + $onAWay + $delivered + $canceled;

        $newPercent         = $new       >= 1 ? $new       / $totalPrice * 100 : 0;
        $acceptedPercent    = $accepted  >= 1 ? $accepted  / $totalPrice * 100 : 0;
        $readyPercent       = $ready     >= 1 ? $ready     / $totalPrice * 100 : 0;
        $onAWayPercent      = $onAWay    >= 1 ? $onAWay    / $totalPrice * 100 : 0;
        $deliveredPercent   = $delivered >= 1 ? $delivered / $totalPrice * 100 : 0;
        $canceledPercent    = $canceled  >= 1 ? $canceled  / $totalPrice * 100 : 0;

        $groupCompleted = ($new + $accepted + $ready + $onAWay);
        $groupPercent   = ($newPercent + $acceptedPercent + $readyPercent + $onAWayPercent);

        return [
            'new'        => [
                'sum'       => $new,
                'percent'   => $newPercent,
            ],
            'accepted'   => [
                'sum'       => $accepted,
                'percent'   => $acceptedPercent,
            ],
            'ready'      => [
                'sum'       => $ready,
                'percent'   => $readyPercent,
            ],
            'on_a_way'   => [
                'sum'       => $onAWay,
                'percent'   => $onAWayPercent,
            ],
            'delivered'  => [
                'sum'       => $delivered,
                'percent'   => $deliveredPercent,
            ],
            'canceled'   => [
                'sum'       => $canceled,
                'percent'   => $canceledPercent,
            ],
            'group'      => [
                'active'    => [
                    'sum'       => $groupCompleted,
                    'percent'   => $groupPercent,
                ],
                'completed' => [
                    'sum'       => $delivered,
                    'percent'   => $deliveredPercent,
                ],
                'ended'     => [
                    'sum'       => $canceled,
                    'percent'   => $canceledPercent,
                ]
            ]
        ];
    }
}
