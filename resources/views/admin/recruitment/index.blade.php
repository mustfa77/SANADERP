@extends('layouts.app')

@section('title', __('recruitment.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('recruitment.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">{{ __('recruitment.title') }} <span class="text-muted font-size-14">({{ $orders->total() }})</span></h4>
                <div class="d-flex gap-2">
                    @can('export', App\Models\RecruitmentOrder::class)
                        <a href="{{ route('admin.recruitment.export', $filters) }}" class="btn btn-outline-success">
                            <i class="ri-file-excel-2-line align-middle me-1"></i> {{ __('common.export_excel') }}
                        </a>
                    @endcan
                    @can('create', App\Models\RecruitmentOrder::class)
                        <a href="{{ route('admin.recruitment.create') }}" class="btn btn-primary">
                            <i class="ri-add-line align-middle me-1"></i> {{ __('recruitment.create') }}
                        </a>
                    @endcan
                </div>
            </div>

            <form method="GET" action="{{ route('admin.recruitment.index') }}" class="row g-2 mb-4">
                <div class="col-lg-3 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('recruitment.search_placeholder') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <select name="status" class="form-select">
                        <option value="">{{ __('recruitment.fields.status') }}: {{ __('common.all') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach (['country_id' => $countries, 'profession_id' => $professions, 'branch_id' => $branches] as $column => $options)
                    <div class="col-lg-2 col-md-6">
                        <select name="{{ $column }}" class="form-select">
                            <option value="">{{ __('recruitment.fields.'.$column) }}: {{ __('common.all') }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}" @selected((string) ($filters[$column] ?? '') === (string) $option->id)>{{ $option->trans('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-lg-1 col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill" title="{{ __('common.filter') }}"><i class="ri-filter-2-line"></i></button>
                    <a href="{{ route('admin.recruitment.index') }}" class="btn btn-light flex-fill" title="{{ __('common.reset') }}"><i class="ri-close-line"></i></a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-centered table-nowrap table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('recruitment.fields.number') }}</th>
                            <th>{{ __('recruitment.fields.client_id') }}</th>
                            <th>{{ __('recruitment.fields.country_id') }}</th>
                            <th>{{ __('recruitment.fields.worker_id') }}</th>
                            <th>{{ __('recruitment.fields.branch_id') }}</th>
                            <th>{{ __('recruitment.fields.created_at') }}</th>
                            <th>{{ __('recruitment.fields.status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><a href="{{ route('admin.recruitment.show', $order) }}" class="fw-semibold"><code dir="ltr">{{ $order->number }}</code></a></td>
                                <td>{{ $order->client?->trans('name') }}</td>
                                <td>{{ $order->country?->trans('name') }} · <span class="text-muted">{{ $order->profession?->trans('name') }}</span></td>
                                <td>{{ $order->worker?->trans('name') ?? '—' }}</td>
                                <td>{{ $order->branch?->trans('name') }}</td>
                                <td dir="ltr" class="text-start">{{ $order->created_at->format('Y-m-d') }}</td>
                                <td><x-recruitment-status :status="$order->status" /></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.recruitment.show', $order) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('common.view') }}" data-bs-toggle="tooltip"><i class="ri-eye-line"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $orders->links() }}</div>
        </div>
    </div>
@endsection
