<?php

namespace App\Exports;

use App\Models\Language;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductExport extends BaseExport implements FromCollection, WithHeadings
{
    protected array $filter;

    public function __construct(array $filter)
    {
        $this->filter = $filter;
    }

    public function collection(): Collection
    {
        $language = Language::where('default', 1)->first();

        $products = Product::filter($this->filter)
            ->with([
                'category.translation'  => fn($q) => $q->where('locale', data_get($this->filter, 'language'))
                    ->orWhere('locale', $language),

                'unit.translation'      => fn($q) => $q->where('locale', data_get($this->filter, 'language'))
                    ->orWhere('locale', $language),

                'translation'           => fn($q) => $q->where('locale', data_get($this->filter, 'language'))
                    ->orWhere('locale', $language),

                'brand:id,title',
            ])
            ->orderBy('id')
            ->get();

        return $products->map(fn(Product $product) => $this->tableBody($product));
    }

    public function headings(): array
    {
        return [
            '#',
            'Uu Id',
            'Product Title',
            'Product Description',
            'Shop Id',
            'Shop Name',
            'Category Id',
            'Category Title',
            'Brand Id',
            'Brand Title',
            'Unit Id',
            'Unit Title',
            'Keywords',
            'Tax',
            'Active',
            'Qr Code',
            'Status',
            'Min Qty',
            'Max Qty',
            'Img Urls',
            'Created At',
            'Vegetarian',
            'Kcal',
            'Carbs',
            'Protein',
            'Fats',
            'Addon',
        ];
    }

    private function tableBody(Product $product): array
    {
        return [
            'id'             => $product->id,
            'uuid'           => $product->uuid,
            'title'          => $product->translation?->title,
            'description'    => $product->translation?->description,
            'shop_id'        => $product->shop_id,
            'shop_title'     => $product->shop?->translation?->title,
            'category_id'    => $product->category_id ?? 0,
            'category_title' => $product->category?->translation?->title,
            'brand_id'       => $product->brand_id ?? 0,
            'brand_title'    => $product->brand?->title ?? 0,
            'unit_id'        => $product->unit_id ?? 0,
            'unit_title'     => $product->unit?->translation?->title,
            'keywords'       => $product->keywords ?? '',
            'tax'            => $product->tax ?? 0,
            'active'         => $product->active ? 'active' : 'inactive',
            'qr_code'        => $product->qr_code ?? '',
            'status'         => $product->status ?? Product::PENDING,
            'min_qty'        => $product->min_qty ?? 0,
            'max_qty'        => $product->max_qty ?? 0,
            'img_urls'       => $this->imageUrl($product->galleries) ?? '',
            'created_at'     => $product->created_at ?? date('Y-m-d H:i:s'),
            'vegetarian'     => $product->vegetarian,
            'kcal'           => $product->kcal,
            'carbs'          => $product->carbs,
            'protein'        => $product->protein,
            'fats'           => $product->fats,
            'addon'          => $product->addon,
        ];
    }
}
