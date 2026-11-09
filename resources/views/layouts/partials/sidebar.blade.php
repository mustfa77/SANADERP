@php
    // Sidebar groups: [title-key, items]. Each item: icon, label key, and either
    // 'route' (single link) or 'children' (list of label keys for the sub-menu).
    // Replace '#' links with real routes as the ERP modules are built.
    $menu = [
        ['title_menu', [
            ['icon' => 'mdi mdi-home-variant-outline', 'label' => 'dashboard', 'route' => 'dashboard', 'badge' => ['primary', 3]],
            ['icon' => 'mdi mdi-calendar-outline', 'label' => 'calendar'],
            ['icon' => 'mdi mdi-email-outline', 'label' => 'email', 'children' => ['inbox', 'read_email', 'email_compose']],
        ]],
        ['title_layouts', [
            ['icon' => 'mdi mdi-gradient', 'label' => 'vertical', 'children' => ['light_sidebar', 'compact_sidebar', 'icon_sidebar', 'boxed_layout']],
            ['icon' => 'mdi mdi-page-layout-header', 'label' => 'horizontal', 'children' => ['default', 'topbar_dark', 'boxed_width']],
        ]],
        ['title_pages', [
            ['icon' => 'mdi mdi-account-circle-outline', 'label' => 'authentication', 'children' => ['login', 'register', 'recover_password', 'lock_screen']],
            ['icon' => 'mdi mdi-format-page-break', 'label' => 'utility', 'children' => ['starter_page', 'maintenance', 'coming_soon', 'timeline', 'faqs', 'pricing', 'error_404', 'error_500']],
        ]],
        ['title_components', [
            ['icon' => 'mdi mdi-briefcase-variant-outline', 'label' => 'ui_elements', 'children' => [
                'alerts', 'badge', 'breadcrumb', 'buttons', 'cards', 'carousel', 'dropdowns', 'grid', 'images',
                'lightbox', 'modals', 'offcanvas', 'range_slider', 'session_timeout', 'pagination', 'progress_bars',
                'placeholders', 'sweet_alert', 'tabs_accordions', 'typography', 'toasts', 'video', 'popovers_tooltips', 'rating',
            ]],
            ['icon' => 'ri-eraser-fill', 'label' => 'forms', 'badge' => ['danger', 8], 'children' => [
                'form_elements', 'form_validation', 'form_advanced', 'form_editors', 'form_uploads', 'form_xeditable', 'form_wizard', 'form_mask',
            ]],
            ['icon' => 'ri-table-2', 'label' => 'tables', 'children' => ['basic_tables', 'data_tables', 'responsive_table', 'editable_table']],
            ['icon' => 'ri-bar-chart-line', 'label' => 'charts', 'children' => ['apex_charts', 'chartjs_charts', 'flot_charts', 'knob_charts', 'sparkline_charts']],
            ['icon' => 'ri-brush-line', 'label' => 'icons', 'children' => ['remix_icons', 'material_design', 'dripicons', 'font_awesome']],
            ['icon' => 'ri-map-pin-line', 'label' => 'maps', 'children' => ['google_maps', 'vector_maps']],
        ]],
    ];
@endphp

<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                @foreach ($menu as [$groupTitle, $items])
                    <li class="menu-title">{{ __('app.menu.' . $groupTitle) }}</li>

                    @foreach ($items as $item)
                        <li>
                            @if (! empty($item['children']))
                                <a href="javascript: void(0);" class="{{ empty($item['badge']) ? 'has-arrow ' : '' }}waves-effect">
                                    <i class="{{ $item['icon'] }}"></i>
                                    @isset($item['badge'])
                                        <span class="badge rounded-pill bg-{{ $item['badge'][0] }} float-end">{{ $item['badge'][1] }}</span>
                                    @endisset
                                    <span>{{ __('app.menu.' . $item['label']) }}</span>
                                </a>
                                <ul class="sub-menu" aria-expanded="false">
                                    @foreach ($item['children'] as $child)
                                        <li><a href="#">{{ __('app.menu.' . $child) }}</a></li>
                                    @endforeach
                                </ul>
                            @else
                                <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}" class="waves-effect">
                                    <i class="{{ $item['icon'] }}"></i>
                                    @isset($item['badge'])
                                        <span class="badge rounded-pill bg-{{ $item['badge'][0] }} float-end">{{ $item['badge'][1] }}</span>
                                    @endisset
                                    <span>{{ __('app.menu.' . $item['label']) }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endforeach

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="ri-share-line"></i>
                        <span>{{ __('app.menu.multi_level') }}</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="true">
                        <li><a href="javascript: void(0);">{{ __('app.menu.level', ['n' => '1.1']) }}</a></li>
                        <li><a href="javascript: void(0);" class="has-arrow">{{ __('app.menu.level', ['n' => '1.2']) }}</a>
                            <ul class="sub-menu" aria-expanded="true">
                                <li><a href="javascript: void(0);">{{ __('app.menu.level', ['n' => '2.1']) }}</a></li>
                                <li><a href="javascript: void(0);">{{ __('app.menu.level', ['n' => '2.2']) }}</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
