@php
    $dir = config('app.available_locales')[app()->getLocale()]['dir'] ?? 'ltr';
    $currentUser = auth()->user();
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir }}">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | {{ $site->brand() }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ $site->imageUrl('favicon') }}">
    @include('layouts.partials.head-css', ['dir' => $dir])
</head>

{{-- Separate layout for external users (agency / driver): horizontal top bar, no admin sidebar. --}}
<body data-layout="horizontal" data-topbar="dark">
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex align-items-center">
                    <div class="navbar-brand-box text-center">
                        <span class="logo logo-light">
                            <span class="logo-lg"><img src="{{ $site->imageUrl('logo_light') }}" alt="{{ $site->brand() }}" height="24"></span>
                        </span>
                    </div>
                    <h5 class="text-white mb-0 ms-3 d-none d-md-block">@yield('portal_title')</h5>
                </div>

                <div class="d-flex">
                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ri-global-line align-middle font-size-20"></i>
                            <span class="d-none d-sm-inline-block align-middle ms-1">{{ config('app.available_locales.'.app()->getLocale().'.name') }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach (config('app.available_locales') as $code => $locale)
                                <a href="{{ route('lang.switch', $code) }}" class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}">{{ $locale['name'] }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="dropdown d-inline-block user-dropdown">
                        <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="rounded-circle header-profile-user" src="{{ $currentUser->avatarUrl() }}" alt="">
                            <span class="d-none d-xl-inline-block ms-1">{{ $currentUser->displayName() }}</span>
                            <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="ri-shut-down-line align-middle me-1"></i> {{ __('app.logout') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="topnav">
            <div class="container-fluid">
                <nav class="navbar navbar-light navbar-expand-lg topnav-menu">
                    <div class="collapse navbar-collapse show" id="topnav-menu-content">
                        <ul class="navbar-nav">
                            @yield('portal_nav')
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <x-alerts :summary="false" />
                    @yield('content')
                </div>
            </div>
            @include('layouts.partials.footer')
        </div>
    </div>

    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
