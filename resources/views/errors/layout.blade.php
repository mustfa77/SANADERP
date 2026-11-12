@php
    $dir = config('app.available_locales')[app()->getLocale()]['dir'] ?? 'ltr';
    $code = (string) $code;
    $digits = str_split($code);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir }}">

<head>
    <meta charset="utf-8" />
    <title>{{ __("errors.{$code}.title") }} | {{ $site->brand() }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ $site->imageUrl('favicon') }}">
    @include('layouts.partials.head-css', ['dir' => $dir])
</head>

{{-- Based on _template/layouts/pages-404.html --}}
<body>
    <div class="my-5 pt-5">
        <div class="w-100">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="text-center">
                            <div>
                                <h1 class="display-2 error-text fw-bold" dir="ltr">{{ $digits[0] }}<i class="ri-ghost-smile-fill align-bottom text-primary mx-1"></i>{{ implode('', array_slice($digits, 2)) }}</h1>
                            </div>
                            <div>
                                <h4 class="text-uppercase mt-4">{{ __("errors.{$code}.title") }}</h4>
                                <p style="white-space: pre-line;">{{ $message ?? __("errors.{$code}.message") }}</p>
                                <div class="mt-4">
                                    @if ($code === '503' && auth()->check())
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary">{{ __('app.logout') }}</button>
                                        </form>
                                    @else
                                        <a href="{{ url('/') }}" class="btn btn-primary">{{ __('errors.back_home') }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
