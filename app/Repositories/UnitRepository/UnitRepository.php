<?php

namespace App\Repositories\UnitRepository;

use App\Models\Language;
use App\Models\Unit;
use App\Repositories\CoreRepository;
use Illuminate\Support\Facades\Cache;

class UnitRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Unit::class;
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function unitsPaginate(array $filter = []): mixed
    {

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ])
           ->when(data_get($filter, 'active'), function ($q, $active) {
               $q->where('active', $active);
           })
           ->when(data_get($filter, 'search'), function ($q, $search) {
               $q->whereHas('translations', function ($q) use($search) {
                   $q->where('title', 'LIKE', "%$search%");
               });
           })
           ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
           ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function unitDetails(int $id): mixed
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ])
            ->find($id);
    }

}
