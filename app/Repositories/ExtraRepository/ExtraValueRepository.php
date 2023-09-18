<?php

namespace App\Repositories\ExtraRepository;

use App\Models\ExtraValue;
use App\Models\Language;
use App\Repositories\CoreRepository;

class ExtraValueRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ExtraValue::class;
    }

    public function extraValueList(array $filter)
    {
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		return $this->model()
            ->with([
				'group.shop:id,uuid',
				'group.shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'group' => fn($q) => $q->when(data_get($filter, 'shop_id'), function ($q, $shopId) use($filter) {

					$q->where('shop_id', $shopId);

					if (!isset($filter['is_admin'])) {
						$q->orWhereNull('shop_id');
					}

				}),
                'group.translation' => fn($q) => $q->where('locale', $this->language)
            ])
			->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where(function ($query) use ($shopId, $filter) {

				$query->whereHas('group', function ($q) use ($shopId, $filter) {

					$q->where('shop_id', $shopId);

					if (!isset($filter['is_admin'])) {
						$q->orWhereNull('shop_id');
					}

				});

			}))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'group_id'), fn($q, $groupId) => $q->where('extra_group_id', $groupId))
            ->when(request('deleted_at'), fn($q) => $q->onlyTrashed())
            ->orderBy('id', 'desc')
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function extraValueDetails(int $id)
    {
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		return $this->model()
            ->with([
				'group.shop:id,uuid',
				'group.shop.translation' => fn($q) => $q->select('id', 'locale', 'title', 'shop_id')
					->where('locale', $this->language)->orWhere('locale', $locale),

                'galleries'         => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title'),
                'group.translation' => fn($q) => $q->where('locale', $this->language)
            ])
            ->find($id);
    }

}
