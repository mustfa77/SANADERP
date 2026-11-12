{{-- Top-bar bell: open alerts of the user's branches (types the user may see). --}}
@can('alerts.view')
    @php
        $bellQuery = \App\Models\Alert::query()->open()->visibleTo(auth()->user());
        $bellCount = (clone $bellQuery)->count();
        $bellAlerts = $bellCount ? (clone $bellQuery)->orderByRaw("case when level = 'danger' then 0 else 1 end")->orderBy('due_at')->limit(6)->get() : collect();
    @endphp
    <div class="dropdown d-inline-block">
        <button type="button" class="btn header-item noti-icon waves-effect" id="alerts-bell" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __('alerts.title') }}">
            <i class="ri-notification-3-line"></i>
            @if ($bellCount)
                <span class="noti-dot"></span>
                <span class="badge bg-danger rounded-pill position-absolute top-0 start-50 font-size-10" style="margin-top: 14px;">{{ $bellCount > 99 ? '99+' : $bellCount }}</span>
            @endif
        </button>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="alerts-bell" style="min-width: 320px;">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0">{{ __('alerts.title') }}</h6>
                <span class="badge bg-light text-dark">{{ $bellCount }}</span>
            </div>
            <div style="max-height: 320px; overflow-y: auto;">
                @forelse ($bellAlerts as $alert)
                    <a href="{{ $alert->url() ?? route('admin.alerts.index') }}" class="text-reset d-flex p-3 border-bottom">
                        <div class="avatar-xs me-2 flex-shrink-0">
                            <span class="avatar-title rounded-circle bg-{{ $alert->level }}-subtle text-{{ $alert->level }}"><i class="{{ $alert->type->icon() }}"></i></span>
                        </div>
                        <div class="flex-grow-1 font-size-12">
                            <div class="fw-semibold">{{ $alert->type->label() }}</div>
                            <div class="text-muted">{{ $alert->message() }}</div>
                        </div>
                    </a>
                @empty
                    <p class="text-muted text-center p-3 mb-0 font-size-13">{{ __('alerts.none') }}</p>
                @endforelse
            </div>
            <div class="p-2 border-top d-grid">
                <a class="btn btn-sm btn-link font-size-13 text-center" href="{{ route('admin.alerts.index') }}">{{ __('alerts.view_all') }}</a>
            </div>
        </div>
    </div>
@endcan
