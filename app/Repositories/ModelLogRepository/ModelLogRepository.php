<?php

namespace App\Repositories\ModelLogRepository;

use App\Models\ModelLog;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;

class ModelLogRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ModelLog::class;
    }

    /**
     * This is only for users route
     * @param array $filter
     * @param string $paginate
     * @return Paginator
     */
    public function paginate(array $filter = [], string $paginate = 'simplePaginate'): Paginator
    {
        /** @var ModelLog $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'createdBy:id,firstname,lastname',
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->$paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $id
     * @return ModelLog|null
     */
    public function show(int $id): ?ModelLog
    {
        return ModelLog::with([
                'createdBy',
                'modelType'
            ])
            ->find($id);
    }
}
