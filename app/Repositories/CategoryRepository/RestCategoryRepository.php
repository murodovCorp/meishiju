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
            ->withThreeChildren($filter + ['lang' => $this->language])
            ->updatedDate($this->updatedDate)
            ->filter($filter)
            ->where(fn($q) => $q->where('parent_id', null)->orWhere('parent_id', 0))
            ->whereHas('translation',
                fn($q) => $q->select('id', 'locale', 'title', 'category_id')->where('locale', $this->language),
            )
            ->when(data_get($filter, 'receipt-count'), fn($q) => $q->withCount('receipts'))
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

}
