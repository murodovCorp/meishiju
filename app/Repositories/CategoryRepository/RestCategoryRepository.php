<?php

namespace App\Repositories\CategoryRepository;

use App\Models\Category;
use App\Repositories\CoreRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class RestCategoryRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Category::class;
    }

    /**
     * Get Parent, only categories where parent_id == 0
     */
    public function parentCategories(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();

        return $category
            ->withThreeChildren(['language' => $this->language])
            ->updatedDate($this->updatedDate)
            ->filter($filter)
            ->where(fn($q) => $q->where('parent_id', null)->orWhere('parent_id', 0))
            ->whereHas('translation',
                fn($q) => $q->select('id', 'locale', 'title', 'category_id')->where('locale', $this->language),
            )
            ->select([
                'id',
                'uuid',
                'keywords',
                'parent_id',
                'type',
                'img',
                'active',
                'deleted_at',
            ])
            ->when(data_get($filter, 'receipt-count'), fn($q) => $q->withCount('receipts'))
            ->orderByDesc('id')
            ->paginate(data_get($filter, 'perPage', 10));
    }

}
