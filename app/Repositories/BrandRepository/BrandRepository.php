<?php

namespace App\Repositories\BrandRepository;

use App\Models\Brand;
use App\Repositories\CoreRepository;

class BrandRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Brand::class;
    }

    public function brandsList(array $array = [])
    {
        return $this->model()
            ->updatedDate($this->updatedDate)
            ->filter($array)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Get brands with pagination
     */
    public function brandsPaginate(array $filter = [])
    {
        return $this->model()
            ->withCount([
                'products' => fn($q) => $q->whereHas('shop', fn($q) => $q->whereNull('deleted_at') )
                    ->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
            ])
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get one brands by Identification number
     */
    public function brandDetails(int $id)
    {
        return $this->model()->find($id);
    }

    public function brandsSearch(array $filter = [])
    {
        return $this->model()
            ->withCount('products')
            ->when(data_get($filter, 'search'), fn($q, $search) => $q->where('title', 'LIKE', "%$search%"))
            ->when(isset($filter['active']), fn($q) => $q->whereActive($filter['active']))
            ->orderBy(data_get($filter,'column','id'), data_get($filter,'sort','desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }
}
