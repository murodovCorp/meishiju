@foreach($discounts as $discount)
<i>
Название: {{ $discount['title'] }}
@isset($discount['description'])
Описание: {{ str_replace("&nbsp;", '', strip_tags($discount['description'])) }}
@endisset
Дата окончания акции: {{ $discount['date_end']->format('d.m.Y H:s') }}
</i>
@endforeach
