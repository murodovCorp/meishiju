<?php

namespace App\Repositories\DashboardRepository;

use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param $time
     * @param $filter
     * @return Builder|mixed
     */
    public function preDataStatistic($time, $filter): mixed
    {
        return DB::table('orders')
            ->where('orders.created_at', '>=', date('Y-m-d 00:00:01', strtotime(now()->{$time}())))
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('orders.shop_id', $shopId));
    }

    /**
     * @param array $filter
     * @return array
     */
    public function ordersStatistics(array $filter = []): array
    {
        $time       = data_get($filter, 'time', 'subYear');
        $delivered  = Order::STATUS_DELIVERED;
        $canceled   = Order::STATUS_CANCELED;
        $new        = Order::STATUS_NEW;
        $accepted   = Order::STATUS_ACCEPTED;
        $ready      = Order::STATUS_READY;
        $onAWay     = Order::STATUS_ON_A_WAY;
        $date       = date('Y-m-d 00:00:01');

        $productsOutOfStock = Product::whereHas('stocks', fn($q) => $q->where('quantity', '<=', 0))
		->where('addon', false)
        ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', '=', $shopId))
        ->select(['id', 'addon', 'shop_id'])
        ->count();

        $productsCount = Product::select('id')
			->where('addon', false)
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', '=', $shopId))
			->select(['id', 'addon', 'shop_id'])
			->count();

        $reviews = Review::when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q
				->whereHasMorph('assignable', Shop::class, function ($query) use($shopId) {
					$query->where('assignable_id', '=', $shopId);
				})
				->whereHasMorph('reviewable', Order::class, function ($query) use($shopId) {
            		$query->where('shop_id', '=', $shopId);
            	}),
			fn($q) => $q->whereHasMorph('reviewable', Order::class)
        )
			->count('id');

        $orders = $this->preDataStatistic($time, $filter)
            ->select([
                DB::raw("count(if(orders.created_at >= '$date', 1, null)) as today_count"),
                DB::raw("count(orders.id) as orders_count"),
                DB::raw("count(if(orders.status = '$canceled', 1, null)) as cancel_orders_count"),
                DB::raw("count(if(orders.status = '$new', 1, null)) as new"),
                DB::raw("count(if(orders.status = '$accepted', 1, null)) as accepted"),
                DB::raw("count(if(orders.status = '$ready', 1, null)) as ready"),
                DB::raw("count(if(orders.status = '$onAWay', 1, null)) as on_a_way"),
                DB::raw("count(if(orders.status = '$delivered', 1, null)) as delivered_orders_count"),
                DB::raw("count(if(orders.status = '$new', 1, null) or if(orders.status = '$accepted', 1, null) or if(orders.status = '$ready', 1, null) or if(orders.status = '$onAWay', 1, null)) as progress_orders_count"),
                DB::raw("sum(if(orders.status   = '$delivered', orders.total_price, 0)) as total_earned"),
                DB::raw("sum(if(orders.status   = '$delivered', orders.delivery_fee, 0)) as delivery_earned"),
                DB::raw("sum(if(orders.status   = '$delivered', orders.tax, 0)) as tax_earned"),
                DB::raw("sum(if(orders.status   = '$delivered', orders.commission_fee, 0)) as commission_earned"),
            ])
            ->first();

        return collect($orders)
            ->merge([
                'products_out_of_count' => $productsOutOfStock,
                'products_count'        => $productsCount,
                'reviews_count'         => $reviews
            ])
            ->toArray();
    }

    public function ordersChart(array $filter = []): Collection
    {
        $time = data_get($filter, 'time', 'subYear');

        return $this->preDataStatistic($time, $filter)
            ->select([
                DB::raw("sum(distinct orders.total_price) as total_price"),
                DB::raw("count(distinct orders.id) as count"),
                DB::raw("(DATE_FORMAT(orders.created_at, " . ($time == 'subYear' ? "'%Y" :
                        ($time == 'subMonth' ? "'%Y-%m" : "'%Y-%m-%d")) . "')) as time"
                ),
            ])
            ->groupBy('time')
            ->get();
    }

    public function productsStatistic(array $filter = []): Paginator
    {
        $time    = data_get($filter, 'time', 'subYear');
        $perPage = data_get($filter, 'perPage', 5);
        $default = data_get(Language::where('default', 1)->first(['locale', 'default']), 'locale');

        if ($perPage > 100) {
            $perPage = 100;
        }

        return DB::table('products as p')
            ->join('product_translations as pt', 'p.id', '=', 'pt.product_id')
            ->join('stocks as s', 'p.id', '=', 's.countable_id')
            ->join('order_details as od', 's.id', '=', 'od.stock_id')
            ->join('orders as o', 'od.order_id', '=', 'o.id')
            ->where('o.status', Order::STATUS_DELIVERED)
            ->whereDate('o.created_at', '>=', now()->{$time}())
            ->where(fn($q) => $q->where('pt.locale', '=', $this->language)->orWhere('locale', $default))
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('o.shop_id', $shopId))
            ->where('s.countable_type', '=', Product::class)
            ->whereNull('s.deleted_at')
            ->select([
                DB::raw("pt.title as title"),
                DB::raw("p.id as id"),
                DB::raw("p.img as img"),
                DB::raw("count(distinct o.id) as count"),
            ])
            ->groupBy(['title', 'id'])
            ->having('count', '>', '0')
            ->orderBy('count', 'desc')
            ->simplePaginate($perPage);
    }

    public function usersStatistic(array $filter = []): Paginator
    {
        $time    = data_get($filter, 'time', 'subYear');
        $perPage = data_get($filter, 'perPage', 5);

        if ($perPage > 100) {
            $perPage = 100;
        }

        return $this->preDataStatistic($time, $filter)
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->crossJoin('users as u', 'orders.user_id', '=', 'u.id')
            ->select([
                DB::raw("u.id as id"),
                DB::raw("u.firstname as firstname"),
                DB::raw("u.img as img"),
                DB::raw("u.lastname as lastname"),
                DB::raw("u.phone as phone"),
                DB::raw("sum(distinct orders.total_price) as total_price"),
                DB::raw("count(distinct orders.id) as count"),
            ])
            ->groupBy('id')
            ->having('total_price', '>', '0')
            ->orHaving('count', '>', '0')
            ->orderBy('total_price', 'desc')
            ->simplePaginate($perPage);
    }

    /**
     * @param array $filter
     * @return array
     */
    public function orderByStatusStatistics(array $filter = []): array
    {
        $delivered = Order::STATUS_DELIVERED;
        $canceled  = Order::STATUS_CANCELED;
        $new       = Order::STATUS_NEW;
        $accepted  = Order::STATUS_ACCEPTED;
        $cooking   = Order::STATUS_COOKING;
        $ready     = Order::STATUS_READY;
        $onAWay    = Order::STATUS_ON_A_WAY;

        $date      = date('Y-m-d 00:00:01');

        $result    = [
            'count'                 => 0,
            'total_price'           => 0,
            $delivered              => 0,
            $canceled               => 0,
            $new                    => 0,
            $accepted               => 0,
            $cooking                => 0,
            $ready                  => 0,
            $onAWay                 => 0,
            'today_count'           => 0,
            'total_delivered_price' => 0,
            'last_delivered_fee'    => 0,
        ];

//        $filter['date_from'] = date('Y-m-d H:i:s', strtotime('-1 minute'));

        $status = data_get($filter, 'status');
        $total  = 0;

        unset($filter['status']);

        Order::filter($filter)
            ->select(['id', 'total_price', 'status', 'delivery_fee', 'created_at'])
            ->chunkMap(function (Order $order) use (&$result, $date, $delivered, $canceled, $new, $accepted, $cooking, $ready, $onAWay, $status, &$total) {

                $result['count'] += 1;
                $result['total_price'] += $order->total_price;

                if ($order->status === Order::STATUS_DELIVERED) {
                    $result['total_delivered_price'] += $order->total_price;
                }

                if ($order->status === Order::STATUS_DELIVERED) {
                    $result['last_delivered_fee'] = $order->delivery_fee;
                }

                if ($order->created_at >= $date) {
                    $result['today_count'] += 1;
                }

                switch ($order->status) {
                    case $delivered:
                        $result[$delivered] += 1;
                        if ($status === $delivered) {
                            $total += 1;
                        }
                        break;
                    case $canceled:
                        $result[$canceled] += 1;
                        if ($status === $canceled) {
                            $total += 1;
                        }
                        break;
                    case $new:
                        $result[$new] += 1;
                        if ($status === $new) {
                            $total += 1;
                        }
                        break;
                    case $accepted:
                        $result[$accepted] += 1;
                        if ($status === $accepted) {
                            $total += 1;
                        }
                        break;
                    case $cooking:
                        $result[$cooking] += 1;
                        if ($status === $cooking) {
                            $total += 1;
                        }
                        break;
                    case $ready:
                        $result[$ready] += 1;
                        if ($status === $ready) {
                            $total += 1;
                        }
                        break;
                    case $onAWay:
                        $result[$onAWay] += 1;
                        if ($status === $onAWay) {
                            $total += 1;
                        }
                        break;
                }

            });

        $progress = $result[$new] + $result[$accepted] + $result[$cooking] + $result[$ready] + $result[$onAWay];

        return [
            'progress_orders_count'  => $progress,
            'delivered_orders_count' => data_get($result, $delivered),
            'total_delivered_price'  => data_get($result, 'total_delivered_price'),
            'last_delivered_fee'     => data_get($result, 'last_delivered_fee'),
            'cancel_orders_count'    => data_get($result, $canceled),
            'new_orders_count'       => data_get($result, $new),
            'accepted_orders_count'  => data_get($result, $accepted),
            'cooking_orders_count'   => data_get($result, $cooking),
            'ready_orders_count'     => data_get($result, $ready),
            'on_a_way_orders_count'  => data_get($result, $onAWay),
            'orders_count'           => data_get($result, 'count'),
            'total_price'            => data_get($result, 'total_price'),
            'today_count'            => data_get($result, 'today_count'),
            'total'                  => !$status ? data_get($result, 'count') : $total,
        ];
    }

    /**
     * @param int $perPage
     * @param array $statistic
     * @param string|null $status
     * @return int
     */
    public function getLastPage(int $perPage = 10, array $statistic = [], ?string $status = null): int
    {
        $lastPage = match ($status) {
            'new'       => ceil(data_get($statistic, 'new_orders_count') / $perPage),
            'accepted'  => ceil(data_get($statistic, 'accepted_orders_count') / $perPage),
            'cooking'   => ceil(data_get($statistic, 'cooking_orders_count') / $perPage),
            'ready'     => ceil(data_get($statistic, 'ready_orders_count') / $perPage),
            'on_a_way'  => ceil(data_get($statistic, 'on_a_way_orders_count') / $perPage),
            'delivered' => ceil(data_get($statistic, 'delivered_orders_count') / $perPage),
            'canceled'  => ceil(data_get($statistic, 'cancel_orders_count') / $perPage),
            default     => ceil(data_get($statistic, 'orders_count') / $perPage),
        };

        return (int)$lastPage;
    }
}
