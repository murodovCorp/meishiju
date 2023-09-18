<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockReportExport extends BaseExport implements FromCollection, WithHeadings
{
    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn(Product $product) => $this->tableBody($product));
    }

    public function headings(): array
    {
        return [
            'Product title',
            'Status',
            'Stock',
        ];
    }

    private function tableBody(Product $product): array
    {
        return [
            'title'     => $product->translation?->title,
            'status'    => $product->status ?: data_get(Product::STATUSES, $product->status),
            'stock'     => $product->stocks_sum_quantity
        ];
    }
}
