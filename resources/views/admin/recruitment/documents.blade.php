@extends('layouts.app')

@section('title', __('recruitment.documents.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('recruitment.documents.title'), 'parent' => __('recruitment.title')])

    <div class="row">
        @foreach (['total' => ['ri-team-line', 'primary'], 'complete' => ['ri-checkbox-circle-line', 'success'], 'incomplete' => ['ri-error-warning-line', 'danger']] as $key => [$icon, $color])
            <div class="col-md-4">
                <a href="{{ route('admin.recruitment.documents', [...$filters, 'completeness' => $key === 'total' ? null : $key]) }}" class="card text-reset">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar-sm me-3">
                            <span class="avatar-title rounded bg-{{ $color }}-subtle text-{{ $color }} font-size-20"><i class="{{ $icon }}"></i></span>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $summary[$key] }}</h4>
                            <div class="text-muted">{{ __('recruitment.documents.summary.'.$key) }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <p class="text-muted font-size-13">{{ __('recruitment.documents.hint') }}</p>

            <form method="GET" action="{{ route('admin.recruitment.documents') }}" class="row g-2 mb-4">
                <div class="col-lg-3 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('recruitment.search_worker') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <select name="status" class="form-select">
                        <option value="">{{ __('recruitment.fields.status') }}: {{ __('common.all') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach (['country_id' => $countries, 'profession_id' => $professions] as $column => $options)
                    <div class="col-lg-2 col-md-6">
                        <select name="{{ $column }}" class="form-select">
                            <option value="">{{ __('recruitment.fields.'.$column) }}: {{ __('common.all') }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}" @selected((string) ($filters[$column] ?? '') === (string) $option->id)>{{ $option->trans('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-lg-2 col-md-6">
                    <select name="completeness" class="form-select">
                        <option value="">{{ __('recruitment.documents.completeness') }}: {{ __('common.all') }}</option>
                        @foreach (['incomplete', 'complete'] as $option)
                            <option value="{{ $option }}" @selected(($filters['completeness'] ?? '') === $option)>{{ __('recruitment.documents.summary.'.$option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-6 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-centered table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('recruitment.fields.worker_id') }}</th>
                            <th>{{ __('recruitment.fields.country_id') }}</th>
                            <th>{{ __('recruitment.fields.profession_id') }}</th>
                            <th>{{ __('recruitment.fields.status') }}</th>
                            <th style="min-width: 160px;">{{ __('recruitment.documents.completeness') }}</th>
                            <th>{{ __('recruitment.documents.missing') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workers as $worker)
                            @php
                                $progress = $worker->document_progress;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.workers.show', $worker) }}" class="fw-semibold">{{ $worker->trans('name') }}</a>
                                    <div class="text-muted font-size-12" dir="ltr">{{ $worker->number() }}</div>
                                </td>
                                <td>{{ $worker->country?->trans('name') }}</td>
                                <td>{{ $worker->profession?->trans('name') }}</td>
                                <td><x-worker-status :status="$worker->status" /></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar {{ $progress['complete'] ? 'bg-success' : 'bg-warning' }}" style="width: {{ $progress['percent'] }}%"></div>
                                        </div>
                                        <span class="font-size-12" dir="ltr">{{ $progress['uploaded'] }}/{{ $progress['required'] }}</span>
                                    </div>
                                </td>
                                <td class="text-wrap">
                                    @if ($progress['complete'])
                                        <span class="text-success"><i class="ri-check-double-line me-1"></i>{{ __('recruitment.documents.all_uploaded') }}</span>
                                    @else
                                        @foreach ($progress['missing'] as $name)
                                            <span class="badge bg-danger-subtle text-danger mb-1">{{ $name }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.workers.show', [$worker, 'tab' => 'documents']) }}" class="btn btn-sm {{ $progress['complete'] ? 'btn-outline-success' : 'btn-primary' }}">
                                        <i class="{{ $progress['complete'] && in_array($worker->country_id, $authorityCountries) ? 'ri-printer-line' : 'ri-upload-2-line' }} me-1"></i>{{ $progress['complete'] && in_array($worker->country_id, $authorityCountries) ? __('recruitment.documents.print') : __('recruitment.documents.open') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('recruitment.documents.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $workers->links() }}</div>
        </div>
    </div>
@endsection
