<!doctype html>
<html lang="en">
<?php
/** @var App\Models\Order $order */
/** @var App\Models\OrderDetail $detail */
/** @var string $logo */
$userName  = "{$order->user?->firstname} {$order->user?->lastname}";
$userPhone = $order->phone ?? $order->user?->phone;

$shopPhone = $order->shop?->phone ?? $order->shop?->seller?->phone;
$address   = data_get($order, 'address.address', '');
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, shrink-to-fit=no"
    >
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Order {{$order?->id}}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        html {
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }

        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
        }

        .subtitle {
            margin-top: 50px;
        }

        .space {
            margin-top: 300px;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-between">
    <div class="float-left">
        <img class="logo" src="{{$logo}}" alt="logo"/>
    </div>
    <div class="float-right">
        <h1 class="title">Invoice #{{ $order->id }}</h1>
        <h2 class="title gray">{{ $order->created_at?->format('Y-m-d') }}</h2>
    </div>
</div>
<div class="container d-flex justify-content-between" style="margin-top: 100px">
    <div class="float-left" style="margin-right: 50px">
        <h3 class="subtitle">Address</h3>
        <div class="address__info">
            <div class="address__info--item">{!! $order->shop?->translation?->address ?? '' !!}</div>
            <div class="address__info--item">
                {!! !empty($shopPhone) ? '+' . str_replace('+', '', $shopPhone) : '' !!}
            </div>
        </div>
        <h3 class="subtitle">Address place</h3>
        <div class="address__info">
            <div class="address__info--item">{!! $userName !!}</div>
            <div class="address__info--item">{!! $address !!}</div>
            <div class="address__info--item">{!! data_get($order, 'address.floor', '') .
                data_get($order, 'address.house', '') . data_get($order, 'address.office', '') !!}
            </div>
            <div class="address__info--item">
                {!! !empty($userPhone) ? '+' . str_replace('+', '', $userPhone) : '' !!}
            </div>
        </div>
    </div>
</div>
<div class="space"></div>
<table class="table table-striped mt-4 table-bordered">
    <thead>
    <tr>
        <th scope="col">#</th>
        <th scope="col">From</th>
        <th scope="col">Number</th>
        <th scope="col">Date</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <th scope="row">{{ $order->id }}</th>
        <td>{{ $order->created_at?->format('Y-m-d') }}</td>
        <td>{{ $order->id }}</td>
        <td>{{ $order->created_at?->format('Y-m-d') }}</td>
    </tr>
    </tbody>
</table>
<table class="table table-striped mt-4 table-bordered"> {{-- style="page-break-after: always;" --}}
    <thead>
    <tr>
        <th scope="col">#</th>
        <th scope="col">Product</th>
        <th scope="col">Total Tax</th>
        <th scope="col">Price without tax</th>
        <th scope="col">Quantity</th>
        <th scope="col">Total price without tax</th>
    </tr>
    </thead>
    <tbody>
        @foreach($order->orderDetails as $orderDetail)
            <tr>
                <th scope="row">#{{$orderDetail->id}}</th>
                <td>
                    {{ $orderDetail->stock?->countable?->translation?->title }}
                    @php
                        $addons = '';
                        $orderDetail->children?->transform(function ($i) use(&$addons, $order) {
                            $addons .= $i?->stock?->countable?->translation?->title . " x $i?->quantity {$order->currency?->symbol}$i?->rate_total_price, ";
                        });
                        $addons = substr($addons, 0, -2);
                    @endphp
                    @if(!empty($addons))
                        <p style="border-radius: 10px; border: 1px solid black; background: white; color: black">
                            {{ $addons }}
                        </p>
                    @endif
                </td>
                <td>{{ $orderDetail->rate_tax * $orderDetail->quantity }}</td>
                <td>{{ $order->currency?->symbol }} {{ $orderDetail->rate_origin_price }}</td>
                <td>{{ $orderDetail->quantity }}</td>
                <td>{{ $order->currency?->symbol }} {{$orderDetail->rate_total_price * $orderDetail->quantity }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="space"></div>
<table class="table table-striped mt-4 table-bordered">
    <thead>
    <tr>
        <th scope="col">Details</th>
        <th scope="col">Tax %</th>
        <th scope="col">Price {{ $order->currency?->symbol }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <th scope="row">{{$order->shop?->translation?->title}}</th>
        <td>{{ $order->shop?->tax ?? 1 }}</td>
        <td>{{ $order->rate_tax }}</td>
    </tr>
    </tbody>
</table>
<table class="table table-striped table-bordered">
    <thead>
    <tr>
        <th scope="col">Price</th>
        <th scope="col">Delivery fee</th>
        <th scope="col">Price {{ $order->currency?->symbol }}</th>
        <th scope="col">Total price {{ $order->currency?->symbol }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <th scope="row">{{ ($order->rate_total_price - $order->rate_tax) . ' ' . $order->currency?->symbol }}</th>
        <td>{{ $order->rate_delivery_fee . ' ' . $order->currency?->symbol }}</td>
        <td>{{ $order->rate_total_price . ' ' . $order->currency?->symbol }}</td>
        <td>{{ $order->rate_total_price . ' ' . $order->currency?->symbol }}</td>
    </tr>
    </tbody>
</table>
<div class="container float-left">
    <p><b>Delivery Date:</b> {{ $order->delivery_date }}</p>
    <p><b>Delivery Time:</b> {{ $order->delivery_time }}</p>
</div>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>
