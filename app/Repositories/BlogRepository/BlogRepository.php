<?php

namespace App\Repositories\BlogRepository;

use App\Models\Blog;
use App\Models\Language;
use App\Repositories\CoreRepository;

class BlogRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Blog::class;
    }

    /**
     * Get brands with pagination
     * @param array $filter
     * @return mixed
     */
    public function blogsPaginate(array $filter = []): mixed
    {
        return $this->model()
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->language);
            })
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)
                    ->select('id', 'locale', 'blog_id', 'title', 'short_desc')
            ])
            ->when(data_get($filter, 'type'), function ($q, $type) {
                $q->where('type', data_get(Blog::TYPES, $type));
            })
            ->when(isset($filter['deleted_at']), fn($q) => $q->onlyTrashed())
            ->when(data_get($filter, 'active'), function ($q, $active) {
                $q->where('active', $active);
            })
            ->when(data_get($filter, 'published_at'), function ($q) {
                $q->whereNotNull('published_at');
            })
            ->orderBy(data_get($filter,'column','id'), data_get($filter,'sort','desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get brands with pagination
     */
    public function blogByUUID(string $uuid)
    {
        return $this->model()
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->language);
            })
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)
            ])
            ->firstWhere('uuid', $uuid);
    }

    public function lastShow()
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
            ->whereHas('translation', function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            })
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            ])
            ->where('type', 1)
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
