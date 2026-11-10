@extends('layouts.app')

@section('title', __('users.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('users.title')])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h4 class="card-title mb-0">{{ __('users.title') }}
                            <span class="text-muted font-size-14">({{ $users->total() }})</span></h4>
                        @can('create', App\Models\User::class)
                            <a href="{{ route('admin.users.create') }}" class="btn btn-primary waves-effect waves-light">
                                <i class="ri-user-add-line align-middle me-1"></i> {{ __('users.create') }}
                            </a>
                        @endcan
                    </div>

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 mb-4">
                        <div class="col-lg-4 col-md-6">
                            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control"
                                placeholder="{{ __('users.search_placeholder') }}">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <select name="role" class="form-select">
                                <option value="">{{ __('users.fields.roles') }}: {{ __('common.all') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>{{ $role->displayName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <select name="branch" class="form-select">
                                <option value="">{{ __('users.fields.branches') }}: {{ __('common.all') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) ($filters['branch'] ?? '') === (string) $branch->id)>{{ $branch->trans('name') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-1 col-md-4">
                            <select name="status" class="form-select">
                                <option value="">{{ __('users.fields.status') }}</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-1 col-md-4">
                            <select name="type" class="form-select">
                                <option value="">{{ __('users.fields.user_type') }}</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-12 d-flex gap-2">
                            <button type="submit" class="btn btn-soft-primary btn-outline-primary flex-fill"><i class="ri-filter-2-line"></i> {{ __('common.filter') }}</button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-light flex-fill">{{ __('common.reset') }}</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('users.fields.name') }}</th>
                                    <th>{{ __('users.fields.phone') }}</th>
                                    <th>{{ __('users.fields.roles') }}</th>
                                    <th>{{ __('users.fields.branches') }}</th>
                                    <th>{{ __('users.fields.user_type') }}</th>
                                    <th>{{ __('users.fields.status') }}</th>
                                    <th>{{ __('users.fields.last_login_at') }}</th>
                                    <th class="text-end">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $user->avatarUrl() }}" alt="" class="avatar-xs rounded-circle me-2">
                                                <div>
                                                    <h5 class="font-size-14 mb-0">
                                                        <a href="{{ route('admin.users.show', $user) }}" class="text-body">{{ $user->displayName() }}</a>
                                                    </h5>
                                                    <p class="text-muted font-size-12 mb-0" dir="ltr">{{ $user->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span dir="ltr">{{ $user->phone }}</span></td>
                                        <td>
                                            @forelse ($user->roles as $role)
                                                <span class="badge bg-primary-subtle text-primary font-size-12">{{ $role->displayName() }}</span>
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if ($user->permissions->contains('name', 'branches.all') || $user->isSuperAdmin())
                                                <span class="badge bg-info-subtle text-info font-size-12">{{ __('users.fields.all_branches') }}</span>
                                            @else
                                                {{ $user->branches->map->trans('name')->implode('، ') ?: '—' }}
                                            @endif
                                        </td>
                                        <td>{{ $user->user_type->label() }}</td>
                                        <td><x-status-badge :status="$user->status" /></td>
                                        <td><span dir="ltr">{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('common.view') }}" data-bs-toggle="tooltip"><i class="ri-eye-line"></i></a>
                                            @can('update', $user)
                                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-success" title="{{ __('common.edit') }}" data-bs-toggle="tooltip"><i class="ri-pencil-line"></i></a>
                                            @endcan
                                            @if ($user->isActive())
                                                @can('deactivate', $user)
                                                    <x-action-form :action="route('admin.users.deactivate', $user)" method="PATCH"
                                                        :confirm="__('users.actions.deactivate').'?'" :confirm-text="__('users.confirm_deactivate')"
                                                        button-class="btn btn-sm btn-outline-warning" :title="__('users.actions.deactivate')">
                                                        <i class="ri-user-unfollow-line"></i>
                                                    </x-action-form>
                                                @endcan
                                            @else
                                                @can('activate', $user)
                                                    <x-action-form :action="route('admin.users.activate', $user)" method="PATCH"
                                                        button-class="btn btn-sm btn-outline-info" :title="__('users.actions.activate')">
                                                        <i class="ri-user-follow-line"></i>
                                                    </x-action-form>
                                                @endcan
                                            @endif
                                            @can('delete', $user)
                                                <x-action-form :action="route('admin.users.destroy', $user)" method="DELETE"
                                                    :confirm="__('common.are_you_sure')" :title="__('common.delete')">
                                                    <i class="ri-delete-bin-line"></i>
                                                </x-action-form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">{{ __('common.no_results') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $users->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
