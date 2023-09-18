<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Schema;

class CategoryExport extends BaseExport implements FromCollection, WithHeadings
{
    public function __construct(protected string $language, protected array $filter) {}

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        if (empty(data_get($this->filter, 'type'))) {
            $this->filter['type'] = 'main';
        }

        $column = data_get($this->filter, 'column', 'id');

        if (!Schema::hasColumn('categories', $column)) {
            $column = 'id';
        }

        $categories = Category::filter($this->filter)
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->orderBy($column, data_get($this->filter, 'sort', 'desc'))
            ->get();

        return $categories->map(fn(Category $category) => $this->mergeCategories($category));
    }

    /**
     * @param  Category  $category
     * @return array
     */
    private function mergeCategories(Category $category): array
    {
        $categories = [$this->tableBody($category)];

        foreach ($category->children as $child) {
            $categories = array_merge($categories, $this->mergeCategories($child));
        }

        return $categories;
    }
    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Id',
            'Uu Id',
            'Keywords',
            'Parent Id',
            'Title',
            'Description',
            'Active',
            'Status',
            'Type',
            'Img Urls',
        ];
    }

    /**
     * @param Category $category
     * @return array
     */
    private function tableBody(Category $category): array
    {
        return [
            'id'            => $category->id,
            'uuid'          => $category->uuid,
            'keywords'      => $category->keywords,
            'parent_id'     => $category->parent_id,
            'title'         => $category->translation?->title,
            'description'   => $category->translation?->description,
            'active'        => $category->active ? 'active' : 'inactive',
            'status'        => $category->status,
            'type'          => $category->type ? data_get(Category::TYPES_VALUES, $category->type, 'main') : '',
            'img_urls'      => $this->imageUrl($category->galleries),
        ];
    }
}
