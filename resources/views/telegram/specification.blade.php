@switch($section)
@case(1)
<strong>
ИНФОРМАЦИЯ О ДОМЕ
</strong>
Наименование: {{ $home->name ?? $noDataString }}
Застройщик: {{ $home->developer_name ?? $noDataString }}
Адрес: {{ $home->address ?? $noDataString }}
Ввод в эксплуатацию: {{ $home->quarter_end ?? $noDataString }}кв. {{ $home->year_end ?? $noDataString }}
Тип дома: {{ $home->homeType->name ?? $noDataString }}
Выдача ключей: {{ $home->issuance_of_keys ? $home->issuance_of_keys->format('d.m.Y H:s') : $noDataString }}
@break

@case(2)
<strong>
ОСНОВНЫЕ ХАРАКТЕРИСТИКИ
</strong>
Класс недвижимости: {{ $home->homeClass->name ?? $noDataString }}
Класс энергоэффективности: {{ $home->energy_class ?? $noDataString }}
Свободная планировка: {{ $home->is_free_layout ? 'Да' : 'Нет' }}
Тип: отделки: {{ $decors ?: $noDataString }}
Кол-во подъездов: {{ $home->entrances->count()  ?? $noDataString }}
Кол-во этажей: {{ $home->count_floors  ?? $noDataString }}
Кол-во квартир: {{ $home->apartments->count()  ?? $noDataString }}
Жилая площадь, м²: {{ $home->living_area  ?? $noDataString }}
Высота потолков, м: {{ $home->ceiling_height ?? $noDataString }}
Лифт: {{ $home->lift ?? $noDataString }}
@break

@case(3)
<strong>
БЛАГОУСТРОЙСТВО
</strong>
Кол-во детских площадок: {{ $home->count_playgrounds  ?? $noDataString }}
Кол-во спортивных площадок: {{ $home->count_sportgrounds  ?? $noDataString }}
Кол-во площадок для сбора мусора: {{ $home->count_garbage_places  ?? $noDataString }}
@break
@endswitch
