@php
    $dir = config('app.available_locales')[app()->getLocale()]['dir'] ?? 'ltr';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir }}">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | {{ $site->brand() }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="{{ __('app.meta_description') }}" name="description" />
    <link rel="shortcut icon" href="{{ $site->imageUrl('favicon') }}">

    @include('layouts.partials.head-css', ['dir' => $dir])
</head>

{{-- Based on _template/layouts/auth-login.html --}}
<body class="bg-pattern">
    <div class="bg-overlay"></div>
    <div class="account-pages my-5 pt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-4 col-lg-6 col-md-8">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="text-center">
                                <a href="{{ url('/') }}">
                                    <img src="{{ $site->imageUrl('logo_dark') }}" alt="{{ $site->brand() }}" height="24" class="auth-logo logo-dark mx-auto">
                                    <img src="{{ $site->imageUrl('logo_light') }}" alt="{{ $site->brand() }}" height="24" class="auth-logo logo-light mx-auto">
                                </a>
                            </div>

                            @yield('content')
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <p class="mb-2">
                            @foreach (config('app.available_locales') as $code => $locale)
                                <a href="{{ route('lang.switch', $code) }}"
                                    class="mx-1 {{ app()->getLocale() === $code ? 'fw-bold text-white' : 'text-white-50' }}">{{ $locale['name'] }}</a>
                            @endforeach
                        </p>
                        <p class="text-white-50">{{ date('Y') }} © {{ $site->brand() }}. {{ __('app.rights') }}</p>
                    </div>
                </div>
            </div>
            <!-- end row -->
        </div>
    </div>
    <!-- end Account pages -->

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
