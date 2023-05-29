<?php

namespace App\Repositories\UserAddressRepository;

use App\Models\UserAddress;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserAddressRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return UserAddress::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var UserAddress $model */

        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'user:id,firstname,lastname',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param UserAddress $model
     * @return UserAddress
     */
    public function show(UserAddress $model): UserAddress
    {
        return $model->loadMissing(['user']);
    }

    /**
     * @param int $userId
     * @return UserAddress
     */
    public function getActive(int $userId): UserAddress
    {
        return UserAddress::where([
            'active'  => 1,
            'user_id' => $userId
        ])->first();
    }
}
