<?php

namespace App\Exports;

use App\Models\Language;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockExport extends BaseExport implements FromCollection, WithHeadings
{
    protected string $defaultLanguage;
    protected string $language;

    public function __construct()
    {
        $this->defaultLanguage = data_get(
            Language::where('default', 1)->first(['locale', 'default']), 'locale'
        );

        $this->language = request(
            'lang',
            data_get(Language::where('default', 1)->first(['locale', 'default']), 'locale')
        );
    }

    public function collection(): Collection
    {
        $stocks = Stock::with([
                'countable.category.translation'  => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $this->defaultLanguage),

                'countable.translation' => fn($q) => $q->where('locale', $this->language)
                    ->orWhere('locale', $this->defaultLanguage),
                'stockExtras.group',
            ])
            ->whereHas('countable')
            ->orderBy('id')
            ->get();

        return $stocks->map(fn(Stock $stock) => $this->tableBody($stock));
    }

    public function headings(): array
    {
        return [
            '#',
            'Type',
            'Type Id',
            'Title',
            'Price',
            'Quantity',
            'Value Ids',
            'Values',
        ];
    }

    private function tableBody(Stock $stock): array
    {
        return [
           'id'             => $stock->id,
           'countable_type' => Str::after($stock->countable_type, 'App\Models\\'),
           'countable_id'   => $stock->countable_id,
           'title'          => $stock->countable?->translation?->title,
           'price'          => $stock->price,
           'quantity'       => $stock->quantity,
           'extra_value_id' => $stock->stockExtras->implode('id', ','),
           'extra_values'   => $stock->stockExtras->implode('value', ','),
        ];
    }
}
