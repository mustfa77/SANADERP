@extends('layouts.app')

@section('title', $resource->title())

@section('content')
    @include('layouts.partials.page-title', ['title' => $resource->title(), 'parent' => __('permissions.modules.settings')])

    @php
        $canEdit = auth()->user()->can($resource->permission('edit'));
        $canDelete = auth()->user()->can($resource->permission('delete'));
        $sortable = $resource->isSortable() && $canEdit;
        $columns = $resource->listFields();
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">
                    <i class="{{ $resource->icon }} me-1"></i>{{ $resource->title() }}
                    <span class="text-muted font-size-14">({{ $records->total() }})</span>
                </h4>
                @can($resource->permission('create'))
                    <a href="{{ route($resource->routeName('create')) }}" class="btn btn-primary waves-effect waves-light">
                        <i class="ri-add-line align-middle me-1"></i> {{ __('reference.add', ['item' => $resource->singular()]) }}
                    </a>
                @endcan
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route($resource->routeName('index')) }}" class="row g-2 mb-4">
                @if ($resource->search !== [])
                    <div class="col-lg-4 col-md-6">
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('common.search') }}...">
                    </div>
                @endif
                @foreach ($resource->filters as $column => $options)
                    <div class="col-lg-2 col-md-6">
                        <select name="{{ $column }}" class="form-select">
                            <option value="">{{ $resource->fieldLabel($column) }}: {{ __('common.all') }}</option>
                            @foreach ($resource->filterOptions($column) as $value => $label)
                                <option value="{{ $value }}" @selected((string) ($filters[$column] ?? '') === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-lg-2 col-md-6">
                    <select name="status" class="form-select">
                        <option value="">{{ __('reference.fields.is_active') }}: {{ __('common.all') }}</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('common.active') }}</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('common.inactive') }}</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill"><i class="ri-filter-2-line"></i> {{ __('common.filter') }}</button>
                    <a href="{{ route($resource->routeName('index')) }}" class="btn btn-light flex-fill">{{ __('common.reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-centered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            @if ($sortable)
                                <th style="width: 90px;">{{ __('reference.order') }}</th>
                            @endif
                            @foreach ($columns as $name => $field)
                                <th>{{ $resource->fieldLabel($name) }}</th>
                            @endforeach
                            <th>{{ __('reference.fields.is_active') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                @if ($sortable)
                                    <td class="text-nowrap">
                                        <x-reference.move-buttons :action="route($resource->routeName('move'), $record)" />
                                    </td>
                                @endif
                                @foreach ($columns as $name => $field)
                                    <td @if (($field['dir'] ?? null) === 'ltr') dir="ltr" class="text-start" @endif>
                                        @if ($loop->first && $resource->showRoute)
                                            <a href="{{ route($resource->showRoute, $record) }}" class="fw-semibold">{{ $resource->display($record, $name) }}</a>
                                        @elseif ($loop->first)
                                            <span class="fw-semibold">{{ $resource->display($record, $name) }}</span>
                                        @else
                                            {{ $resource->display($record, $name) }}
                                        @endif
                                    </td>
                                @endforeach
                                <td><x-reference.active-badge :active="$record->is_active" /></td>
                                <td class="text-end text-nowrap">
                                    @if ($resource->showRoute)
                                        <a href="{{ route($resource->showRoute, $record) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('common.view') }}" data-bs-toggle="tooltip"><i class="ri-eye-line"></i></a>
                                    @endif
                                    @if ($canEdit)
                                        <a href="{{ route($resource->routeName('edit'), $record) }}" class="btn btn-sm btn-outline-success" title="{{ __('common.edit') }}" data-bs-toggle="tooltip"><i class="ri-pencil-line"></i></a>
                                        @if ($record->is_active)
                                            <x-action-form :action="route($resource->routeName('deactivate'), $record)" method="PATCH"
                                                :confirm="__('reference.deactivate').'?'" :confirm-text="__('reference.confirm_deactivate')"
                                                button-class="btn btn-sm btn-outline-warning" :title="__('reference.deactivate')">
                                                <i class="ri-pause-circle-line"></i>
                                            </x-action-form>
                                        @else
                                            <x-action-form :action="route($resource->routeName('activate'), $record)" method="PATCH"
                                                button-class="btn btn-sm btn-outline-info" :title="__('reference.activate')">
                                                <i class="ri-play-circle-line"></i>
                                            </x-action-form>
                                        @endif
                                    @endif
                                    @if ($canDelete)
                                        <x-action-form :action="route($resource->routeName('destroy'), $record)" method="DELETE"
                                            :confirm="__('common.are_you_sure')" :title="__('common.delete')">
                                            <i class="ri-delete-bin-line"></i>
                                        </x-action-form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 3 }}" class="text-center text-muted py-4">{{ __('common.no_results') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $records->links() }}</div>
        </div>
    </div>
@endsection
