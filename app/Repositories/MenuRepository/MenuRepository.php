<?php

namespace App\Repositories\MenuRepository;

use App\Models\Language;
use App\Models\Menu;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MenuRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Menu::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        /** @var Menu $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param Menu $model
     * @return Menu|null
     */
    public function show(Menu $model): Menu|null
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $model->loadMissing([
            'translations',
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
        ]);
    }
}
