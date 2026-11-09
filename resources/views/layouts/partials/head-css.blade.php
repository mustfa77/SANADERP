@stack('styles')

@if ($dir === 'rtl')
    <!-- Bootstrap Css (RTL) -->
    <link href="{{ asset('assets/css/bootstrap-rtl.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
@else
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
@endif

<!-- Icons Css -->
<link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

@if ($dir === 'rtl')
    <!-- App Css (RTL) -->
    <link href="{{ asset('assets/css/app-rtl.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
@else
    <!-- App Css -->
    <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
@endif

<!-- Cairo font (Arabic & English) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --bs-font-sans-serif: 'Cairo', sans-serif;
        --bs-body-font-family: 'Cairo', sans-serif;
    }

    body,
    h1, h2, h3, h4, h5, h6,
    .h1, .h2, .h3, .h4, .h5, .h6,
    .btn, .form-control, .form-select, .dropdown-item, .badge,
    .menu-title, #sidebar-menu ul li a, .page-title-box h4, .card-title,
    .apexcharts-text, .apexcharts-legend-text, .apexcharts-tooltip {
        font-family: 'Cairo', sans-serif !important;
    }
</style>
