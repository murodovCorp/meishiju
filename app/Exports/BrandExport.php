<?php

namespace App\Exports;

use App\Models\Brand;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandExport extends BaseExport implements FromCollection, WithHeadings
{
    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $brands = Brand::orderBy('id')->get();

        return $brands->map(fn (Brand $brand) => $this->tableBody($brand));
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Id',
            'Uu Id',
            'Title',
            'Active',
            'Img Urls',
        ];
    }

    /**
     * @param Brand $brand
     * @return array
     */
    private function tableBody(Brand $brand): array
    {
        return [
            'id'        => $brand->id,
            'uuid'      => $brand->uuid,
            'title'     => $brand->title,
            'active'    => $brand->active ? 'active' : 'inactive',
            'img_urls'  => $this->imageUrl($brand->galleries),
        ];
    }
}
