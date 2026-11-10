@php
    $locales = config('app.available_locales');
    $dir = $locales[app()->getLocale()]['dir'] ?? 'ltr';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir }}">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | {{ $site->brand() }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="{{ __('app.meta_description') }}" name="description" />
    <meta content="{{ $site->brand() }}" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ $site->imageUrl('favicon') }}">

    @include('layouts.partials.head-css', ['dir' => $dir])
</head>

<body @foreach ($site->bodyAttributes() as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach>

    @if ($site->maintenanceEnabled())
        <div class="alert alert-warning text-center rounded-0 mb-0 py-2" style="position: fixed; bottom: 0; inset-inline: 0; z-index: 1050;">
            <i class="ri-tools-line me-1 align-middle"></i>{{ __('settings.maintenance.active_banner') }}
            <a href="{{ route('admin.settings.edit', ['tab' => 'maintenance']) }}" class="alert-link ms-1">{{ __('settings.title') }}</a>
        </div>
    @endif

    {{-- Start with the default mode from settings until the user picks one in the right bar --}}
    <script>
        try {
            if (! sessionStorage.getItem('is_visited')) {
                sessionStorage.setItem('is_visited', @json($site->appearance('theme_mode').'-mode-switch'));
            }
        } catch (e) {}
    </script>

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('layouts.partials.topbar')

        @include('layouts.partials.sidebar')

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">
                    <x-alerts :summary="false" />

                    @yield('content')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            @include('layouts.partials.footer')

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    @include('layouts.partials.right-sidebar')

    @include('layouts.partials.vendor-scripts')

</body>

</html>
