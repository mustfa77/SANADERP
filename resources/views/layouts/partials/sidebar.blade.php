{{-- Sidebar built from config/menu.php; each item needs its permission. --}}
<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                @foreach (config('menu') as $section => $items)
                    @php($visible = array_filter($items, fn (array $item): bool => auth()->user()->can($item['permission'])))

                    @if ($visible !== [])
                        <li class="menu-title">
                            {{ $section === 'main' ? __('app.nav.main') : __('permissions.sections.'.$section) }}
                        </li>

                        @foreach ($visible as $item)
                            <li>
                                <a href="{{ $item['route'] ? route($item['route']) : 'javascript: void(0);' }}"
                                    class="waves-effect {{ $item['route'] && request()->routeIs(str($item['route'])->beforeLast('.')->append('.*')->toString()) ? 'active' : '' }}">
                                    <i class="{{ $item['icon'] }}"></i>
                                    @unless ($item['route'])
                                        <span class="badge rounded-pill bg-light text-muted float-end">{{ __('common.soon') }}</span>
                                    @endunless
                                    <span>{{ __('permissions.modules.'.$item['key']) }}</span>
                                </a>
                            </li>
                        @endforeach
                    @endif
                @endforeach
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
