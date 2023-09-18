<?php

namespace App\Repositories\ExtraRepository;

use App\Models\ExtraGroup;
use App\Models\Language;
use App\Repositories\CoreRepository;

class ExtraGroupRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ExtraGroup::class;
    }

    public function extraGroupList(array $filter = [])
    {
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		return $this->model()
            ->with([
				'shop:id,uuid',
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'translation' => fn($q) => $q
                    ->when(data_get($filter, 'search'), fn ($q, $search) => $q->where('title', 'LIKE', "%$search%"))
            ])
            ->whereHas('translation', fn($q) => $q
                ->when(data_get($filter, 'search'), fn ($q, $search) => $q->where('title', 'LIKE', "%$search%"))
            )
            ->when(data_get($filter, 'active'),  fn($q, $active) => $q->where('active', $active))
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where(function ($query) use ($shopId, $filter) {

				$query->where('shop_id', $shopId);

				if (!isset($filter['is_admin'])) {
					$query->orWhereNull('shop_id');
				}

			}))
            ->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed())
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function extraGroupDetails(int $id)
    {
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		return $this->model()
            ->with([
				'shop:id,uuid',
				'shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'translation' => fn($q) => $q->where('locale', $this->language)
            ])
            ->whereHas('translation', fn($q) => $q->where('locale', $this->language))
            ->find($id);
    }

}
