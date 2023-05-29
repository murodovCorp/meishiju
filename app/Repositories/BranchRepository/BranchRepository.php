<?php

namespace App\Repositories\BranchRepository;

use App\Models\Branch;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Branch::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        /** @var Branch $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'shop.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param Branch $model
     * @return Branch|null
     */
    public function show(Branch $model): Branch|null
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $model->loadMissing([
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            'shop.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
        ]);
    }

    /**
     * @param int $id
     * @return Branch|null
     */
    public function showById(int $id): Branch|null
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return Branch::with([
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            'shop.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
        ])->find($id);
    }
}
