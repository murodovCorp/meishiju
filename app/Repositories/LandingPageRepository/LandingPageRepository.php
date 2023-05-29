<?php

namespace App\Repositories\LandingPageRepository;

use App\Models\LandingPage;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LandingPageRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return LandingPage::class;
    }

    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var LandingPage $model */
        $model = $this->model();

        return $model->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get one brands by Identification number
     */
    public function show(string $type): Builder|Model|null
    {
        return LandingPage::with([
            'galleries'
        ])
            ->where('type', $type)
            ->first();
    }

}
