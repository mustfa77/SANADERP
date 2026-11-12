@extends('layouts.app')

@section('title', $branch->trans('name'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('branches.show'), 'parent' => __('branches.title')])

    <div class="row">
        {{-- Branch card --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <span class="avatar-title rounded-circle font-size-20">{{ mb_substr($branch->code, 0, 3) }}</span>
                    </div>
                    <h5 class="mb-1">{{ $branch->trans('name') }}</h5>
                    <p class="text-muted mb-2"><code dir="ltr">{{ $branch->code }}</code></p>
                    <span class="badge font-size-12 {{ $branch->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                        {{ $branch->is_active ? __('branches.active') : __('branches.inactive') }}
                    </span>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        @can('update', $branch)
                            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-success"><i class="ri-pencil-line me-1"></i>{{ __('common.edit') }}</a>
                            @if ($branch->is_active)
                                <x-action-form :action="route('admin.branches.deactivate', $branch)" method="PATCH"
                                    :confirm="__('branches.actions.deactivate').'?'" :confirm-text="__('branches.confirm_deactivate')"
                                    button-class="btn btn-sm btn-warning">
                                    <i class="ri-pause-circle-line me-1"></i>{{ __('branches.actions.deactivate') }}
                                </x-action-form>
                            @else
                                <x-action-form :action="route('admin.branches.activate', $branch)" method="PATCH" button-class="btn btn-sm btn-info">
                                    <i class="ri-play-circle-line me-1"></i>{{ __('branches.actions.activate') }}
                                </x-action-form>
                            @endif
                        @endcan
                        @can('delete', $branch)
                            <x-action-form :action="route('admin.branches.destroy', $branch)" method="DELETE" :confirm="__('common.are_you_sure')" button-class="btn btn-sm btn-outline-danger">
                                <i class="ri-delete-bin-line me-1"></i>{{ __('common.delete') }}
                            </x-action-form>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Counters --}}
            <div class="row">
                @foreach (['users_count' => 'ri-user-3-line', 'workers_count' => 'ri-team-line', 'trips_count' => 'ri-bus-line'] as $counter => $icon)
                    <div class="col-4">
                        <div class="card text-center">
                            <div class="card-body p-3">
                                <i class="{{ $icon }} font-size-20 text-primary"></i>
                                <h4 class="mb-0 mt-1">{{ $branch->{$counter} }}</h4>
                                <p class="text-muted font-size-12 mb-0">{{ __('branches.fields.'.$counter) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-xl-8">
            {{-- Details --}}
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">{{ __('branches.sections.details') }}</h4>
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                @foreach ([
                                    'name_ar' => $branch->name_ar,
                                    'name_en' => $branch->name_en,
                                    'city' => $branch->trans('city'),
                                    'address' => $branch->trans('address'),
                                    'phone' => $branch->phone,
                                    'email' => $branch->email,
                                    'manager' => $branch->manager?->displayName(),
                                    'created_at' => $branch->created_at?->format('Y-m-d'),
                                ] as $field => $value)
                                    <tr>
                                        <th class="text-muted fw-normal" style="width: 35%;">{{ $field === 'created_at' ? __('common.created_at') : __('branches.fields.'.$field) }}</th>
                                        <td @if (in_array($field, ['phone', 'email', 'name_en', 'created_at'], true)) dir="ltr" class="text-start" @endif>{{ $value ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Users of the branch --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="card-title mb-0">{{ __('branches.sections.users') }} <span class="text-muted font-size-14">({{ $branch->users_count }})</span></h4>
                        @if ($branch->users_count > $users->count())
                            @can('viewAny', App\Models\User::class)
                                <a href="{{ route('admin.users.index', ['branch' => $branch->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('branches.view_all_users') }}</a>
                            @endcan
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-centered table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('users.fields.name') }}</th>
                                    <th>{{ __('users.fields.roles') }}</th>
                                    <th>{{ __('users.fields.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $user->avatarUrl() }}" alt="" class="avatar-xs rounded-circle me-2">
                                                @can('view', $user)
                                                    <a href="{{ route('admin.users.show', $user) }}" class="text-body">{{ $user->displayName() }}</a>
                                                @else
                                                    {{ $user->displayName() }}
                                                @endcan
                                            </div>
                                        </td>
                                        <td>
                                            @forelse ($user->roles as $role)
                                                <span class="badge bg-primary-subtle text-primary font-size-12">{{ $role->displayName() }}</span>
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        </td>
                                        <td><x-status-badge :status="$user->status" /></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('branches.no_users') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
