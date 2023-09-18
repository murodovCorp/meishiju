<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoryReportExport extends BaseExport implements FromCollection, WithHeadings
{
    protected Collection|array $rows;

    public function __construct(Collection|array $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn(Collection|array $row) => $this->tableBody($row));
    }

    public function headings(): array
    {
        return [
            'Id',
            'Title',
            'Net sales',
            'Item sold',
            'Products',
            'Orders',
            'Created at',
        ];
    }

    private function tableBody(Collection|array $row): array
    {
        return [
            'id'             => data_get($row, 'id', 0),
            'title'          => data_get($row, 'title', ''),
            'quantity'       => data_get($row, 'quantity', 0),
            'price'          => data_get($row, 'price', 0),
            'products_count' => data_get($row, 'products_count', 0),
            'count'          => data_get($row, 'count', 0),
            'created_at'     => data_get($row, 'created_at', 0),
        ];
    }
}
