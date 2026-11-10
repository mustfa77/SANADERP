@extends('layouts.app')

@section('title', $user->displayName())

@section('content')
    @include('layouts.partials.page-title', ['title' => __('users.show'), 'parent' => __('users.title')])

    <div class="row">
        {{-- Profile card --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $user->avatarUrl() }}" alt="" class="avatar-lg rounded-circle img-thumbnail mb-3">
                    <h5 class="mb-1">{{ $user->displayName() }}</h5>
                    <p class="text-muted mb-2" dir="ltr">{{ $user->email }}</p>
                    <x-status-badge :status="$user->status" />
                    <span class="badge bg-light text-body font-size-12">{{ $user->user_type->label() }}</span>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        @can('update', $user)
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-success"><i class="ri-pencil-line me-1"></i>{{ __('common.edit') }}</a>
                        @endcan

                        @if ($user->isActive())
                            @can('deactivate', $user)
                                <x-action-form :action="route('admin.users.deactivate', $user)" method="PATCH"
                                    :confirm="__('users.actions.deactivate').'?'" :confirm-text="__('users.confirm_deactivate')"
                                    button-class="btn btn-sm btn-warning">
                                    <i class="ri-user-unfollow-line me-1"></i>{{ __('users.actions.deactivate') }}
                                </x-action-form>
                            @endcan
                        @else
                            @can('activate', $user)
                                <x-action-form :action="route('admin.users.activate', $user)" method="PATCH" button-class="btn btn-sm btn-info">
                                    <i class="ri-user-follow-line me-1"></i>{{ __('users.actions.activate') }}
                                </x-action-form>
                            @endcan
                        @endif

                        @can('resetPassword', $user)
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reset-password-modal">
                                <i class="ri-lock-password-line me-1"></i>{{ __('users.actions.reset_password') }}
                            </button>
                        @endcan

                        @can('delete', $user)
                            <x-action-form :action="route('admin.users.destroy', $user)" method="DELETE" :confirm="__('common.are_you_sure')" button-class="btn btn-sm btn-outline-danger">
                                <i class="ri-delete-bin-line me-1"></i>{{ __('common.delete') }}
                            </x-action-form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-info" role="tab">
                                <i class="ri-information-line me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __('users.tabs.info') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-roles" role="tab">
                                <i class="ri-shield-user-line me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __('users.tabs.roles') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-branches" role="tab">
                                <i class="ri-git-branch-line me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __('users.tabs.branches') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-activity" role="tab">
                                <i class="ri-history-line me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __('users.tabs.activity') }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content p-3 text-muted">
                        {{-- Info --}}
                        <div class="tab-pane active" id="tab-info" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-nowrap mb-0">
                                    <tbody>
                                        <tr><th class="w-25">{{ __('users.fields.name_ar') }}</th><td dir="rtl" class="text-start">{{ $user->name_ar }}</td></tr>
                                        <tr><th>{{ __('users.fields.name_en') }}</th><td>{{ $user->name_en }}</td></tr>
                                        <tr><th>{{ __('users.fields.email') }}</th><td><span dir="ltr">{{ $user->email }}</span></td></tr>
                                        <tr><th>{{ __('users.fields.phone') }}</th><td><span dir="ltr">{{ $user->phone }}</span></td></tr>
                                        <tr><th>{{ __('users.fields.user_type') }}</th><td>{{ $user->user_type->label() }}</td></tr>
                                        @if ($user->agency)
                                            <tr><th>{{ __('users.fields.agency') }}</th><td>{{ $user->agency->trans('name') }}</td></tr>
                                        @endif
                                        <tr><th>{{ __('users.fields.preferred_language') }}</th><td>{{ config('app.available_locales.'.$user->preferred_language.'.name') }}</td></tr>
                                        <tr><th>{{ __('users.fields.two_factor') }}</th><td>{{ $user->requiresTwoFactor() ? __('common.yes') : __('common.no') }}</td></tr>
                                        <tr><th>{{ __('users.fields.must_change_password') }}</th><td>{{ $user->must_change_password ? __('common.yes') : __('common.no') }}</td></tr>
                                        <tr><th>{{ __('users.fields.last_login_at') }}</th><td><span dir="ltr">{{ $user->last_login_at?->format('Y-m-d H:i') ?? __('common.never') }}</span></td></tr>
                                        <tr><th>{{ __('users.fields.created_by') }}</th><td>{{ $user->creator?->displayName() ?? '—' }}</td></tr>
                                        <tr><th>{{ __('common.created_at') }}</th><td><span dir="ltr">{{ $user->created_at?->format('Y-m-d H:i') }}</span></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Roles --}}
                        <div class="tab-pane" id="tab-roles" role="tabpanel">
                            @forelse ($user->roles as $role)
                                <div class="d-flex align-items-start border-bottom py-3">
                                    <div class="avatar-xs me-3">
                                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary"><i class="ri-shield-keyhole-line"></i></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="font-size-14 mb-1">{{ $role->displayName() }}
                                            <code class="font-size-12 ms-1">{{ $role->name }}</code></h5>
                                        <p class="mb-0">{{ $role->trans('description') }}</p>
                                    </div>
                                    @can('view', $role)
                                        <a href="{{ route('admin.roles.permissions.edit', $role) }}" class="btn btn-sm btn-light">{{ __('roles.permissions') }}</a>
                                    @endcan
                                </div>
                            @empty
                                <p class="mb-0">{{ __('users.no_roles') }}</p>
                            @endforelse
                        </div>

                        {{-- Branches --}}
                        <div class="tab-pane" id="tab-branches" role="tabpanel">
                            @if ($user->hasAllBranches())
                                <span class="badge bg-info-subtle text-info font-size-13">{{ __('users.fields.all_branches') }}</span>
                            @else
                                @forelse ($user->branches as $branch)
                                    <span class="badge bg-light text-body font-size-13 me-1">{{ $branch->trans('name') }} <span dir="ltr">({{ $branch->code }})</span></span>
                                @empty
                                    <p class="mb-0">{{ __('common.none') }}</p>
                                @endforelse
                            @endif
                        </div>

                        {{-- Activity --}}
                        <div class="tab-pane" id="tab-activity" role="tabpanel">
                            <h5 class="font-size-15 mb-3">{{ __('users.activity.logins') }}</h5>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('users.activity.date') }}</th>
                                            <th>{{ __('users.activity.result') }}</th>
                                            <th>{{ __('users.activity.ip') }}</th>
                                            <th>{{ __('users.activity.device') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($loginAttempts as $attempt)
                                            <tr>
                                                <td><span dir="ltr">{{ $attempt->created_at->format('Y-m-d H:i') }}</span></td>
                                                <td>
                                                    @if ($attempt->successful)
                                                        <span class="badge bg-success-subtle text-success">{{ __('users.activity.success') }}</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">{{ __('users.activity.failed') }}</span>
                                                        <span class="font-size-12">{{ __('users.activity.reasons.'.$attempt->failure_reason) }}</span>
                                                    @endif
                                                </td>
                                                <td><span dir="ltr">{{ $attempt->ip_address }}</span></td>
                                                <td class="text-truncate" style="max-width: 220px;" title="{{ $attempt->user_agent }}">{{ $attempt->user_agent }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center">{{ __('common.no_results') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="font-size-15 mb-3">{{ __('users.activity.recent_actions') }}</h5>
                            @include('admin.users._log-list', ['logs' => $recentActions, 'showUser' => false])

                            <h5 class="font-size-15 mb-3 mt-4">{{ __('users.activity.changes') }}</h5>
                            @include('admin.users._log-list', ['logs' => $changes, 'showUser' => true])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reset password modal --}}
    @can('resetPassword', $user)
        <div class="modal fade" id="reset-password-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('users.actions.reset_password') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p dir="ltr" class="text-start">{{ $user->email }}</p>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="force_change" value="1" id="force_change" class="form-check-input" checked>
                            <label class="form-check-label" for="force_change">{{ __('users.actions.force_change') }}</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('users.actions.send') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
