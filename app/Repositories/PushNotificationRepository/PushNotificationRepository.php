<?php

namespace App\Repositories\PushNotificationRepository;

use App\Models\PushNotification;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PushNotificationRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return PushNotification::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        /** @var PushNotification $model */
        $model = $this->model();

        return $model
            ->when(data_get($filter, 'type'), function($q, $type) {

                if (in_array($type, PushNotification::TYPES)) {
                    $q->where('type', $type);
                }

            })
            ->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('user_id', $userId))
            ->when(data_get($filter, 'column', 'read_at'), function (Builder $query, $column) use ($filter) {

                $sort = data_get($filter, 'sort', 'desc');

                if ($column !== 'read_at') {
                    return $query->orderBy($column, $sort);
                }

                return $query->orderByRaw("read_at is null $sort, read_at " . ($sort === 'desc' ? 'asc' : 'desc'));
            })
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $id
     * @param int $userId
     * @return PushNotification|null
     */
    public function show(int $id, int $userId): ?PushNotification
    {
        /** @var PushNotification|null $model */
        $model = PushNotification::with(['user'])
            ->where([
                'id'        => $id,
                'user_id'   => $userId,
            ])
            ->first();

        return $model;
    }

}
