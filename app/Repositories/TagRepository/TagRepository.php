<?php

namespace App\Repositories\TagRepository;

use App\Models\Tag;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TagRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Tag::class;
    }

    public function paginate($data = []): LengthAwarePaginator
    {
        $tags = $this->model();

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        /** @var Tag $tags */
        return $tags
            ->with([
                'product' => fn ($q) => $q->select(['id', 'uuid', 'shop_id', 'category_id', 'brand_id', 'unit_id']),
                'translation' => fn($q) => $q->where('locale', $this->language)
            ])
            ->when(data_get($data, 'product_id'),
                fn(Builder $q, $productId) => $q->where('product_id', $productId)
            )
            ->when(data_get($data, 'shop_id'),
                fn(Builder $q, $shopId) => $q->whereHas('product', fn ($b) => $b->where('shop_id', $shopId))
            )
            ->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
            ->when(isset($data['deleted_at']), fn($q) => $q->onlyTrashed())
            ->orderBy(data_get($data, 'column', 'id'), data_get($data, 'sort', 'desc'))
            ->paginate(data_get($data, 'perPage', 15));
    }

    public function show(Tag $tag): Tag
    {
        return $tag->load([
            'product',
            'translation' => fn($q) => $q->where('locale', $this->language)
        ]);
    }
}
