<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('agency.candidates.*') ? 'active' : '' }}" href="{{ route('agency.candidates.index') }}">
        <i class="ri-user-search-line me-2"></i> {{ __('portal.candidates') }}
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('agency.documents.*') ? 'active' : '' }}" href="{{ route('agency.documents.index') }}">
        <i class="ri-file-list-3-line me-2"></i> {{ __('portal.documents') }}
    </a>
</li>
