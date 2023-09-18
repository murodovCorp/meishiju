<?php

namespace App\Repositories\ReviewRepository;

use App\Models\Blog;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ReviewRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Review::class;
    }

    public function paginate(array $filter, array $customWith = []): LengthAwarePaginator
    {
        $with = [
            'user' => fn($q) => $q->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'email',
                'img',
            ]),
            'assignable',
            'reviewable',
        ];

        $orderWith = [
            'reviewable.shop' => fn($q) => $q->select([
                'id',
                'uuid',
            ]),
            'reviewable.shop.translation' => fn($q) => $q->select([
                'id',
                'locale',
                'title',
                'shop_id',
            ])
                ->where('locale', $this->language),
            'reviewable.shop.translations' => fn($q) => $q->select(['id']),
            'user' => fn($q) => $q->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'email',
                'img',
            ]),
            'assignable',
        ];

        $productWith = [
            'reviewable' => fn($q) => $q->select([
                'id',
                'uuid',
                'shop_id',
                'img',
            ]),
            'reviewable.translations' => fn($q) => $q->select(['id']),
            'reviewable.translation' => fn($q) => $q->select([
                'id',
                'locale',
                'title',
                'product_id',
            ])
                ->where('locale', $this->language),
            'assignable',
        ];

        if (count($customWith) > 0) {
            $with = $customWith;
        } else if (data_get($filter, 'type') === 'order') {
            $with += $orderWith;
        } else if (data_get($filter, 'type') === 'product') {
            $with += $productWith;
        }

        /** @var Review $reviews */
        $reviews = $this->model();

        return $reviews->with($with)
            ->when(data_get($filter, 'type'), function (Builder $query, $type) use($filter) {

                if ($type === 'blog') {
                    $query->whereHasMorph('reviewable', Blog::class);
                } else if ($type === 'order') {
                    $query->whereHasMorph('reviewable', Order::class);
                } else if ($type === 'shop') {
                    $query->whereHasMorph('reviewable', Shop::class);
                } else if ($type === 'parcel') {
                    $query->whereHasMorph('reviewable', ParcelOrder::class);
                } else if ($type === 'product') {
                    $query->whereHasMorph('reviewable', Product::class);
                }

                return $query->when(data_get($filter, 'type_id'), function ($q, $typeId) {
                    $q->where('reviewable_id', $typeId);
                });
            })
            ->when(data_get($filter, 'assign'), function (Builder $query, $assign) use ($filter) {

                if ($assign === 'user') {

                    $query->whereHasMorph('assignable', User::class, function ($q) {
                        $q->whereHas('roles', fn($r) => $r->where('name', 'user'));
                    });

                } else if ($assign === 'deliveryman') {

                    $query->whereHasMorph('assignable', User::class, function ($q) {
                        $q->whereHas('roles', fn($r) => $r->where('name', 'user'));
                    })->whereHas('user.roles', fn($b) => $b->where('name', 'deliveryman'));

                } else if ($assign === 'shop') {

                    $query->whereHasMorph('assignable', Shop::class);

                } else if ($assign === 'waiter') {

                    $query->whereHasMorph('assignable', User::class, function ($q) {
                        $q->whereHas('roles', fn($r) => $r->where('name', 'user'));
                    })->whereHas('user.roles', fn($b) => $b->where('name', 'waiter'));

                }

                return $query->when(data_get($filter, 'assign_id'), function ($q, $assignId) {
                    $q->where('assignable_id', $assignId);
                });
            })
            ->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed())
            ->when(data_get($filter, 'date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateTo = data_get($filter, 'date_to', date('Y-m-d'));

                $query->where(function (Builder $q) use($dateFrom, $dateTo) {
                    $q->where('created_at', '>=', $dateFrom)
                        ->where('created_at', '<=', $dateTo);
                });

            })
            ->when(data_get($filter, 'user_id'), function (Builder $query, $userId) {

                $query->where('user_id', $userId);

            })
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query
                    ->where(function ($query) use ($search) {
                        $query->where('comment', 'LIKE', "%$search")
                            ->orWhereHas('user', fn($q) => $q->where(function ($b) use ($search) {

                                $firstNameLastName = explode(' ', $search);

                                if (data_get($firstNameLastName, 1)) {
                                    return $b
                                        ->where('firstname', 'LIKE', '%' . $firstNameLastName[0] . '%')
                                        ->orWhere('lastname', 'LIKE', '%' . $firstNameLastName[1] . '%');
                                }

                                return $b
                                    ->where('id', 'LIKE', "%$search%")
                                    ->orWhere('firstname', 'LIKE', "%$search%")
                                    ->orWhere('lastname', 'LIKE', "%$search%")
                                    ->orWhere('email', 'LIKE', "%$search%")
                                    ->orWhere('phone', 'LIKE', "%$search%");
                            }));
                    });
            })
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function show(Review $review): Review
    {
        return $review->loadMissing(['reviewable', 'assignable', 'galleries', 'user']);
    }
}
