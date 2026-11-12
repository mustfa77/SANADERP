@php
    $currentUser = auth()->user();
    $selectedBranchId = $currentUser->selectedBranchId();
    // Inactive branches are hidden, except the one currently selected.
    $allowedBranches = \App\Models\Branch::query()
        ->whereKey($currentUser->allowedBranchIds())
        ->where(fn ($query) => $query->where('is_active', true)->when($selectedBranchId, fn ($q) => $q->orWhereKey($selectedBranchId)))
        ->orderBy('code')
        ->get();
@endphp
<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box text-center">
                <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ $site->imageUrl('logo_sm') }}" alt="logo-sm-dark" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ $site->imageUrl('logo_dark') }}" alt="logo-dark" height="24">
                    </span>
                </a>

                <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ $site->imageUrl('logo_sm') }}" alt="logo-sm-light" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ $site->imageUrl('logo_light') }}" alt="logo-light" height="24">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect" id="vertical-menu-btn">
                <i class="ri-menu-2-line align-middle"></i>
            </button>

            <!-- App Search-->
            <form class="app-search d-none d-lg-block">
                <div class="position-relative">
                    <input type="text" class="form-control" placeholder="{{ __('app.search') }}">
                    <span class="ri-search-line"></span>
                </div>
            </form>
        </div>

        <div class="d-flex">

            <!-- Branch switcher (only allowed branches) -->
            @if ($allowedBranches->isNotEmpty())
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false" title="{{ __('app.nav.branch') }}">
                        <i class="ri-git-branch-line align-middle font-size-18"></i>
                        <span class="d-none d-sm-inline-block align-middle ms-1">
                            {{ $allowedBranches->firstWhere('id', $selectedBranchId)?->trans('name') ?? __('app.nav.all_my_branches') }}
                        </span>
                        <i class="mdi mdi-chevron-down d-none d-sm-inline-block"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <form method="POST" action="{{ route('admin.branch.switch') }}">
                            @csrf
                            <button type="submit" name="branch_id" value=""
                                class="dropdown-item {{ $selectedBranchId === null ? 'active' : '' }}">
                                {{ __('app.nav.all_my_branches') }}
                            </button>
                            @foreach ($allowedBranches as $branch)
                                <button type="submit" name="branch_id" value="{{ $branch->id }}"
                                    class="dropdown-item {{ $selectedBranchId === $branch->id ? 'active' : '' }}">
                                    {{ $branch->trans('name') }}
                                </button>
                            @endforeach
                        </form>
                    </div>
                </div>
            @endif

            <!-- Language switcher -->
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false" title="{{ __('app.language') }}">
                    <i class="ri-global-line align-middle font-size-20"></i>
                    <span class="d-none d-sm-inline-block align-middle ms-1">
                        {{ config('app.available_locales.' . app()->getLocale() . '.name') }}
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    @foreach (config('app.available_locales') as $code => $locale)
                        <a href="{{ route('lang.switch', $code) }}"
                            class="dropdown-item notify-item {{ app()->getLocale() === $code ? 'active' : '' }}">
                            <span class="align-middle">{{ $locale['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            @include('layouts.partials._alerts-bell')

            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
                    <i class="ri-fullscreen-line"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block user-dropdown">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="{{ $currentUser->avatarUrl() }}"
                        alt="{{ $currentUser->displayName() }}">
                    <span class="d-none d-xl-inline-block ms-1">{{ $currentUser->displayName() }}</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('admin.profile.edit') }}"><i class="ri-user-line align-middle me-1"></i> {{ __('app.profile') }}</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="ri-shut-down-line align-middle me-1 text-danger"></i> {{ __('app.logout') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon right-bar-toggle waves-effect">
                    <i class="mdi mdi-cog"></i>
                </button>
            </div>

        </div>
    </div>
</header>
