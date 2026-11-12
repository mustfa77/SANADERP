@extends('layouts.app')

@section('title', __('workers.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('workers.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="card-title mb-0">{{ __('workers.title') }} <span class="text-muted font-size-14">({{ $workers->total() }})</span></h4>
                <div class="d-flex gap-2">
                    @can('export', App\Models\Worker::class)
                        <a href="{{ route('admin.workers.export', $filters) }}" class="btn btn-outline-success">
                            <i class="ri-file-excel-2-line align-middle me-1"></i> {{ __('common.export_excel') }}
                        </a>
                    @endcan
                    @can('create', App\Models\Worker::class)
                        <a href="{{ route('admin.workers.create') }}" class="btn btn-primary">
                            <i class="ri-user-add-line align-middle me-1"></i> {{ __('workers.create') }}
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.workers.index') }}" class="row g-2 mb-4">
                <div class="col-12">
                    <div class="btn-group flex-wrap" role="group">
                        <a href="{{ route('admin.workers.index') }}" class="btn btn-sm {{ empty($filters['group']) ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('common.all') }}</a>
                        @foreach (array_keys($statuses) as $group)
                            <a href="{{ route('admin.workers.index', ['group' => $group]) }}" class="btn btn-sm {{ ($filters['group'] ?? '') === $group ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('workers.status_groups.'.$group) }}</a>
                        @endforeach
                    </div>
                    @if (! empty($filters['group']))
                        <input type="hidden" name="group" value="{{ $filters['group'] }}">
                    @endif
                </div>
                <div class="col-lg-3 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('workers.search_name') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <input type="search" name="passport" value="{{ $filters['passport'] ?? '' }}" class="form-control" dir="ltr" placeholder="{{ __('workers.search_passport') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <input type="search" name="iqama" value="{{ $filters['iqama'] ?? '' }}" class="form-control" dir="ltr" placeholder="{{ __('workers.search_iqama') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <select name="status" class="form-select">
                        <option value="">{{ __('workers.fields.status') }}: {{ __('common.all') }}</option>
                        @foreach ($statuses as $group => $cases)
                            <optgroup label="{{ __('workers.status_groups.'.$group) }}">
                                @foreach ($cases as $status)
                                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="country_id" class="form-select">
                        <option value="">{{ __('workers.fields.country_id') }}: {{ __('common.all') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) ($filters['country_id'] ?? '') === (string) $country->id)>{{ $country->trans('name') }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach ([
                    'profession_id' => $professions,
                    'agency_id' => $agencies,
                    'branch_id' => $branches,
                    'camp_id' => $camps,
                ] as $column => $options)
                    <div class="col-lg-2 col-md-6">
                        <select name="{{ $column }}" class="form-select">
                            <option value="">{{ __('workers.fields.'.$column) }}: {{ __('common.all') }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}" @selected((string) ($filters[$column] ?? '') === (string) $option->id)>{{ $option->trans('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                @foreach (['sponsor_type' => $sponsorTypes, 'rental_type' => $rentalTypes] as $column => $cases)
                    <div class="col-lg-2 col-md-6">
                        <select name="{{ $column }}" class="form-select">
                            <option value="">{{ __('workers.fields.'.$column) }}: {{ __('common.all') }}</option>
                            @foreach ($cases as $case)
                                <option value="{{ $case->value }}" @selected(($filters[$column] ?? '') === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-lg-2 col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-fill"><i class="ri-filter-2-line"></i> {{ __('common.filter') }}</button>
                    <a href="{{ route('admin.workers.index') }}" class="btn btn-light flex-fill">{{ __('common.reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-centered table-nowrap table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('workers.fields.name') }}</th>
                            <th>{{ __('workers.fields.nationality') }}</th>
                            <th>{{ __('workers.fields.profession_id') }}</th>
                            <th>{{ __('workers.fields.branch_id') }}</th>
                            <th>{{ __('workers.fields.passport_no') }}</th>
                            <th>{{ __('workers.fields.status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workers as $worker)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @include('admin.workers._avatar', ['worker' => $worker, 'size' => 'xs'])
                                        <div class="ms-2">
                                            <h5 class="font-size-14 mb-0"><a href="{{ route('admin.workers.show', $worker) }}" class="text-body">{{ $worker->trans('name') }}</a></h5>
                                            <span class="text-muted font-size-12"><span dir="ltr">{{ $worker->number() }}</span>{{ $worker->cv_reference_no ? ' · '.$worker->cv_reference_no : '' }} · {{ $worker->sponsor_type->label() }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $worker->country?->trans('nationality') }}</td>
                                <td>{{ $worker->profession?->trans('name') }}</td>
                                <td>{{ $worker->branch?->trans('name') }}</td>
                                <td><x-masked :value="$worker->passport_no" permission="workers.view_sensitive" type="passport" /></td>
                                <td><x-worker-status :status="$worker->status" /></td>
                                <td class="text-end">
                                    @if ($worker->status === \App\Enums\WorkerStatus::ReadyForSelection && $worker->sponsor_type === \App\Enums\SponsorType::Client)
                                        @can('create', App\Models\RecruitmentOrder::class)
                                            <a href="{{ route('admin.recruitment.create', ['worker' => $worker->id]) }}" class="btn btn-sm btn-primary">
                                                <i class="ri-flight-takeoff-line me-1"></i>{{ __('recruitment.direct_order') }}
                                            </a>
                                        @endcan
                                    @endif
                                    <a href="{{ route('admin.workers.show', $worker) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('common.view') }}" data-bs-toggle="tooltip"><i class="ri-eye-line"></i></a>
                                    @can('update', $worker)
                                        <a href="{{ route('admin.workers.edit', $worker) }}" class="btn btn-sm btn-outline-success" title="{{ __('common.edit') }}" data-bs-toggle="tooltip"><i class="ri-pencil-line"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $workers->links() }}</div>
        </div>
    </div>
@endsection
