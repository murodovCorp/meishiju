<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class OrdersRevenueReportExport extends BaseExport implements FromCollection, WithHeadings, WithStrictNullComparison
{
    public function __construct(protected array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(fn($row) => $this->tableBody($row));
    }

    public function headings(): array
    {
        return [
            'Date',
            'Items Sold',
            'Orders',
            'Shipping',
            'Returns',
            'Total sales',
            'Taxes',
        ];
    }

    private function tableBody($row): array
    {
        return [
            'time'              => data_get($row, 'time', 0),
            'count'             => data_get($row, 'count', 0),
            'total_quantity'    => data_get($row, 'total_quantity', 0),
            'delivery_fee'      => data_get($row, 'delivery_fee', 0),
            'canceled_sum'      => data_get($row, 'canceled_sum', 0),
            'total_price'       => data_get($row, 'total_price', 0),
            'tax'               => data_get($row, 'tax', 0),
        ];
    }
}
