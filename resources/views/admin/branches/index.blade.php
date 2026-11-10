@extends('layouts.app')

@section('title', __('branches.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('branches.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">{{ __('branches.title') }} <span class="text-muted font-size-14">({{ $branches->total() }})</span></h4>
                <div class="d-flex gap-2">
                    @can('export', App\Models\Branch::class)
                        <a href="{{ route('admin.branches.export', $filters) }}" class="btn btn-outline-success">
                            <i class="ri-file-excel-2-line align-middle me-1"></i> {{ __('common.export_excel') }}
                        </a>
                    @endcan
                    @can('create', App\Models\Branch::class)
                        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="ri-add-line align-middle me-1"></i> {{ __('branches.create') }}
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.branches.index') }}" class="row g-2 mb-4">
                <div class="col-lg-5 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control"
                        placeholder="{{ __('branches.search_placeholder') }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="status" class="form-select">
                        <option value="">{{ __('branches.fields.status') }}: {{ __('common.all') }}</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('branches.active') }}</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('branches.inactive') }}</option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill"><i class="ri-filter-2-line"></i> {{ __('common.filter') }}</button>
                    <a href="{{ route('admin.branches.index') }}" class="btn btn-light flex-fill">{{ __('common.reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-centered table-nowrap table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('branches.fields.name') }}</th>
                            <th>{{ __('branches.fields.city') }}</th>
                            <th>{{ __('branches.fields.phone') }}</th>
                            <th>{{ __('branches.fields.manager') }}</th>
                            <th class="text-center">{{ __('branches.fields.users_count') }}</th>
                            <th class="text-center">{{ __('branches.fields.workers_count') }}</th>
                            <th>{{ __('branches.fields.status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle font-size-12">{{ mb_substr($branch->code, 0, 3) }}</span>
                                        </div>
                                        <div>
                                            <h5 class="font-size-14 mb-0">
                                                <a href="{{ route('admin.branches.show', $branch) }}" class="text-body">{{ $branch->trans('name') }}</a>
                                            </h5>
                                            <code class="font-size-12" dir="ltr">{{ $branch->code }}</code>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $branch->trans('city') ?: '—' }}</td>
                                <td><span dir="ltr">{{ $branch->phone ?: '—' }}</span></td>
                                <td>{{ $branch->manager?->displayName() ?? '—' }}</td>
                                <td class="text-center">
                                    @can('viewAny', App\Models\User::class)
                                        <a href="{{ route('admin.users.index', ['branch' => $branch->id]) }}">{{ $branch->users_count }}</a>
                                    @else
                                        {{ $branch->users_count }}
                                    @endcan
                                </td>
                                <td class="text-center">{{ $branch->workers_count }}</td>
                                <td>
                                    <span class="badge font-size-12 {{ $branch->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $branch->is_active ? __('branches.active') : __('branches.inactive') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.branches.show', $branch) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('common.view') }}" data-bs-toggle="tooltip"><i class="ri-eye-line"></i></a>
                                    @can('update', $branch)
                                        <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-outline-success" title="{{ __('common.edit') }}" data-bs-toggle="tooltip"><i class="ri-pencil-line"></i></a>
                                        @if ($branch->is_active)
                                            <x-action-form :action="route('admin.branches.deactivate', $branch)" method="PATCH"
                                                :confirm="__('branches.actions.deactivate').'?'" :confirm-text="__('branches.confirm_deactivate')"
                                                button-class="btn btn-sm btn-outline-warning" :title="__('branches.actions.deactivate')">
                                                <i class="ri-pause-circle-line"></i>
                                            </x-action-form>
                                        @else
                                            <x-action-form :action="route('admin.branches.activate', $branch)" method="PATCH"
                                                button-class="btn btn-sm btn-outline-info" :title="__('branches.actions.activate')">
                                                <i class="ri-play-circle-line"></i>
                                            </x-action-form>
                                        @endif
                                    @endcan
                                    @can('delete', $branch)
                                        <x-action-form :action="route('admin.branches.destroy', $branch)" method="DELETE"
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

            <div class="mt-3">{{ $branches->links() }}</div>
        </div>
    </div>
@endsection
