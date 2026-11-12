@extends('layouts.app')

@section('title', __('clients.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('clients.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">{{ __('clients.title') }} <span class="text-muted font-size-14">({{ $clients->total() }})</span></h4>
                <div class="d-flex gap-2">
                    @can('export', App\Models\Client::class)
                        <a href="{{ route('admin.clients.export', $filters) }}" class="btn btn-outline-success">
                            <i class="ri-file-excel-2-line align-middle me-1"></i> {{ __('common.export_excel') }}
                        </a>
                    @endcan
                    @can('create', App\Models\Client::class)
                        <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
                            <i class="ri-user-add-line align-middle me-1"></i> {{ __('clients.create') }}
                        </a>
                    @endcan
                </div>
            </div>

            <form method="GET" action="{{ route('admin.clients.index') }}" class="row g-2 mb-4">
                <div class="col-lg-4 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('clients.search_placeholder') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <input type="search" name="national_id" value="{{ $filters['national_id'] ?? '' }}" class="form-control" dir="ltr" placeholder="{{ __('clients.search_national_id') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <select name="branch_id" class="form-select">
                        <option value="">{{ __('clients.fields.branch_id') }}: {{ __('common.all') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->trans('name') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <select name="status" class="form-select">
                        <option value="">{{ __('clients.fields.is_active') }}: {{ __('common.all') }}</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('common.active') }}</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('common.inactive') }}</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill"><i class="ri-filter-2-line"></i> {{ __('common.filter') }}</button>
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-light flex-fill">{{ __('common.reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-centered table-nowrap table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('clients.fields.name') }}</th>
                            <th>{{ __('clients.fields.national_id') }}</th>
                            <th>{{ __('clients.fields.phone') }}</th>
                            <th>{{ __('clients.fields.city') }}</th>
                            <th>{{ __('clients.fields.branch_id') }}</th>
                            <th class="text-center">{{ __('clients.fields.visa_balance') }}</th>
                            <th>{{ __('clients.fields.is_active') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr>
                                <td><a href="{{ route('admin.clients.show', $client) }}" class="fw-semibold text-body">{{ $client->trans('name') }}</a></td>
                                <td><x-masked :value="$client->national_id" permission="clients.view_sensitive" type="national_id" /></td>
                                <td dir="ltr" class="text-start">{{ $client->phone }}</td>
                                <td>{{ $client->city ?: '—' }}</td>
                                <td>{{ $client->branch?->trans('name') }}</td>
                                <td class="text-center">{{ $client->visa_balance }}</td>
                                <td><x-reference.active-badge :active="$client->is_active" /></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('common.view') }}" data-bs-toggle="tooltip"><i class="ri-eye-line"></i></a>
                                    @can('update', $client)
                                        <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-outline-success" title="{{ __('common.edit') }}" data-bs-toggle="tooltip"><i class="ri-pencil-line"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $clients->links() }}</div>
        </div>
    </div>
@endsection
