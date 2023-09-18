<?php

namespace App\Repositories\RequestModelRepository;

use App\Models\Language;
use App\Models\Product;
use App\Models\RequestModel;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Schema;

class RequestModelRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return RequestModel::class;
    }

    /**
     * Get brands with pagination
     * @param array $filter
     * @return mixed
     */
    public function index(array $filter = []): mixed
    {
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
		$column = data_get($filter,'column','id');

		if (!Schema::hasColumn('request_models', $column)) {
			$column = 'id';
		}

        return $this->model()
            ->filter($filter)
			->with([
				'model.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
				'createdBy'
			])
            ->orderBy($column, data_get($filter,'sort','desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function show(RequestModel $requestModel): RequestModel
	{
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		$with = [
			'model.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
			'createdBy'
		];

		if ($requestModel->model_type === Product::class) {
			$with = [
				'model' => fn($q) => $q->with([
					'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title'),
					'properties' => fn($q) => $q->where('locale', $this->language),
					'stocks.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)
						->orWhere('locale', $locale),
					'stocks.addons.addon' => fn($q) => $q->where('active', true)
						->where('addon', true)
						->where('status', Product::PUBLISHED),
					'stocks.addons.addon.stock',
					'stocks.addons.addon.translation' => fn($q) => $q->where('locale', $this->language)
						->orWhere('locale', $locale),
					'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())
						->where('active', 1),
					'shop.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
					'category' => fn($q) => $q->select('id', 'uuid'),
					'category.translation' => fn($q) => $q->where('locale', $this->language)
						->orWhere('locale', $locale)
						->select('id', 'category_id', 'locale', 'title'),
					'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
					'unit.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
					'reviews.galleries',
					'reviews.user',
					'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
					'tags.translation' => fn($q) => $q->select('id', 'category_id', 'locale', 'title')
						->where('locale', $this->language)->orWhere('locale', $locale),
				]),
				'createdBy',
			];
		}

        return $requestModel->loadMissing($with);
    }

	/**
	 * @param int $id
	 * @return Builder|Collection|Model|null
	 */
	public function showById(int $id): Builder|Collection|Model|null
	{
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
			->with([
				'model.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
				'createdBy'
			])
			->find($id);
    }
}
