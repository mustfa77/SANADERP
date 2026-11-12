{{-- Sidebar built from config/menu.php; each item (or sub-item) needs its permission. --}}
@php
    $menuUser = auth()->user();
    $canSee = fn (array $item): bool => $item['permission'] === null || $menuUser->can($item['permission']);
    $isActive = fn (array $item): bool => $item['route'] !== null && request()->routeIs(
        $item['active'] ?? str($item['route'])->beforeLast('.')->append('.*')->toString()
    ) && collect($item['params'] ?? [])->every(fn ($value, $key): bool => request($key) === $value);
    $menu = collect(config('menu'))->map(fn (array $items): array => collect($items)
        ->map(function (array $item) use ($canSee): ?array {
            if (isset($item['children'])) {
                $item['children'] = array_values(array_filter($item['children'], $canSee));

                return $item['children'] === [] ? null : $item;
            }

            return $canSee($item) ? $item : null;
        })
        ->filter()
        ->values()
        ->all());
@endphp
<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                @foreach ($menu as $section => $items)
                    @if ($items !== [])
                        <li class="menu-title">
                            {{ $section === 'main' ? __('app.nav.main') : __('permissions.sections.'.$section) }}
                        </li>

                        @foreach ($items as $item)
                            @if (isset($item['children']))
                                @php
                                    $childActive = collect($item['children'])->contains(fn (array $child): bool => $isActive($child));
                                @endphp
                                <li class="{{ $childActive ? 'mm-active' : '' }}">
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="{{ $item['icon'] }}"></i>
                                        <span>{{ __('permissions.modules.'.$item['key']) }}</span>
                                    </a>
                                    <ul class="sub-menu {{ $childActive ? 'mm-show' : '' }}" aria-expanded="{{ $childActive ? 'true' : 'false' }}">
                                        @foreach ($item['children'] as $child)
                                            <li class="{{ $isActive($child) ? 'mm-active' : '' }}">
                                                <a href="{{ route($child['route'], $child['params'] ?? []) }}" class="{{ $isActive($child) ? 'active' : '' }}">
                                                    {{ __('permissions.modules.'.$child['key']) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li>
                                    <a href="{{ $item['route'] ? route($item['route'], $item['params'] ?? []) : 'javascript: void(0);' }}"
                                        class="waves-effect {{ $isActive($item) ? 'active' : '' }}">
                                        <i class="{{ $item['icon'] }}"></i>
                                        @unless ($item['route'])
                                            <span class="badge rounded-pill bg-light text-muted float-end">{{ __('common.soon') }}</span>
                                        @endunless
                                        <span>{{ __('permissions.modules.'.$item['key']) }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
