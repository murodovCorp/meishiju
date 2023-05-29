<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel 9 Custom Login Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<nav class="navbar navbar-light navbar-expand-lg mb-5" style="background-color: #e3f2fd;">
    <div class="container">
        <a class="navbar-brand mr-auto" href="#">Converter Application</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav">
{{--                @guest--}}

{{--                    <li class="nav-item">--}}
{{--                        <a class="nav-link" href="{{ route('login') }}">Login</a>--}}
{{--                    </li>--}}
{{--                    <li class="nav-item">--}}
{{--                        <a class="nav-link" href="{{ route('registration') }}">Register</a>--}}
{{--                    </li>--}}

{{--                @else--}}

                    <li class="nav-item">
                        @if(Auth::user()?->img != '')
                            <a class="nav-link" href="#">
                                <b>
                                    Welcome <img src="{{ asset('images/' . Auth::user()?->img ) }}" width="35" class="rounded-circle" />&nbsp;
                                    {{ Auth::user()?->firstname }}
                                </b>
                            </a>
                        @else
                            <a class="nav-link" href="#">
                                <b>
                                    Welcome <img src="{{ asset('images/no-image.jpg') }}" width="35" class="rounded-circle" />&nbsp;
                                    {{ Auth::user()?->firstname }}
                                </b>
                            </a>
                        @endif
                    </li>

{{--                    <li class="nav-item">--}}
{{--                        <a class="nav-link" href="{{ route('profile') }}">Profile</a>--}}
{{--                    </li>--}}

{{--                    <li class="nav-item">--}}
{{--                        <a class="nav-link" href="{{ route('logout') }}">Logout</a>--}}
{{--                    </li>--}}

{{--                @endguest--}}
            </ul>

        </div>
    </div>
</nav>
<div class="container-fluid mt-5">

    @yield('content')

</div>

</body>
</html>
