@extends('layouts.app')

@section('title', __('roles.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('roles.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">{{ __('roles.title') }} <span class="text-muted font-size-14">({{ $roles->count() }})</span></h4>
                @can('create', App\Models\Role::class)
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary waves-effect waves-light">
                        <i class="ri-add-line align-middle me-1"></i> {{ __('roles.create') }}
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-centered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('roles.fields.display_name') }}</th>
                            <th>{{ __('roles.fields.name') }}</th>
                            <th>{{ __('roles.fields.description') }}</th>
                            <th class="text-center">{{ __('roles.fields.users_count') }}</th>
                            <th class="text-center">{{ __('roles.fields.permissions_count') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td>
                                    <h5 class="font-size-14 mb-0">{{ $role->displayName() }}</h5>
                                    @if ($role->is_system)
                                        <span class="badge bg-secondary-subtle text-secondary font-size-11">{{ __('common.system') }}</span>
                                    @endif
                                    @if ($role->requires_2fa)
                                        <span class="badge bg-warning-subtle text-warning font-size-11"><i class="ri-shield-check-line"></i> {{ __('roles.badge_2fa') }}</span>
                                    @endif
                                </td>
                                <td><code dir="ltr">{{ $role->name }}</code></td>
                                <td class="text-muted" style="min-width: 220px;">{{ $role->trans('description') }}</td>
                                <td class="text-center">
                                    @can('viewAny', App\Models\User::class)
                                        <a href="{{ route('admin.users.index', ['role' => $role->name]) }}">{{ $role->users_count }}</a>
                                    @else
                                        {{ $role->users_count }}
                                    @endcan
                                </td>
                                <td class="text-center">{{ $role->isSuperAdmin() ? __('common.all') : $role->permissions_count }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.roles.permissions.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ri-key-2-line me-1"></i>{{ __('roles.permissions') }}
                                    </a>
                                    @can('update', $role)
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-success" title="{{ __('common.edit') }}" data-bs-toggle="tooltip"><i class="ri-pencil-line"></i></a>
                                    @endcan
                                    @can('clone', $role)
                                        <x-action-form :action="route('admin.roles.clone', $role)" button-class="btn btn-sm btn-outline-secondary" :title="__('roles.clone')">
                                            <i class="ri-file-copy-line"></i>
                                        </x-action-form>
                                    @endcan
                                    @can('delete', $role)
                                        <x-action-form :action="route('admin.roles.destroy', $role)" method="DELETE" :confirm="__('common.are_you_sure')" :title="__('common.delete')">
                                            <i class="ri-delete-bin-line"></i>
                                        </x-action-form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
