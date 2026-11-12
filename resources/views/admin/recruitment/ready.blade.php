@extends('layouts.app')

@section('title', __('recruitment.ready.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('recruitment.ready.title'), 'parent' => __('recruitment.title')])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                <h4 class="card-title mb-0">{{ __('recruitment.ready.title') }} <span class="text-muted font-size-14">({{ $workers->total() }})</span></h4>
            </div>
            <p class="text-muted font-size-13">{{ __('recruitment.ready.hint') }}</p>

            <form method="GET" action="{{ route('admin.recruitment.ready') }}" class="row g-2">
                <div class="col-lg-4 col-md-6">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('recruitment.search_worker') }}">
                </div>
                @foreach (['country_id' => $countries, 'profession_id' => $professions] as $column => $options)
                    <div class="col-lg-3 col-md-6">
                        <select name="{{ $column }}" class="form-select">
                            <option value="">{{ __('recruitment.fields.'.$column) }}: {{ __('common.all') }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}" @selected((string) ($filters[$column] ?? '') === (string) $option->id)>{{ $option->trans('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-lg-2 col-md-6 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="ri-search-line me-1"></i>{{ __('common.search') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @forelse ($workers as $worker)
            @php
                $progress = $worker->document_progress;
            @endphp
            <div class="col-xxl-3 col-lg-4 col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start mb-3">
                            <div class="flex-shrink-0 me-3">
                                @if ($worker->photo_path)
                                    <img src="{{ route('admin.workers.photo', $worker) }}" alt="" class="rounded" style="width: 64px; height: 76px; object-fit: cover;">
                                @else
                                    <div class="avatar-md"><span class="avatar-title rounded bg-light text-muted font-size-24"><i class="ri-user-3-line"></i></span></div>
                                @endif
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="font-size-15 mb-1 text-truncate">
                                    <a href="{{ route('admin.workers.show', $worker) }}" class="text-reset">{{ $worker->trans('name') }}</a>
                                </h5>
                                <div class="text-muted font-size-12" dir="ltr">{{ $worker->number() }}{{ $worker->cv_reference_no ? ' · '.$worker->cv_reference_no : '' }}</div>
                                <div class="mt-1">
                                    <span class="badge bg-primary-subtle text-primary">{{ $worker->profession?->trans('name') }}</span>
                                    <span class="badge bg-info-subtle text-info">{{ $worker->country?->trans('nationality') }}</span>
                                </div>
                            </div>
                        </div>

                        <ul class="list-unstyled font-size-13 text-muted mb-3">
                            <li><i class="ri-cake-2-line me-1"></i>{{ __('recruitment.ready.age') }}: {{ $worker->birth_date ? $worker->birth_date->age : '—' }}</li>
                            <li><i class="ri-briefcase-line me-1"></i>{{ __('workers.fields.experience_years') }}: {{ $worker->experience_years ?? '—' }}</li>
                            <li><i class="ri-money-dollar-circle-line me-1"></i>{{ __('workers.fields.salary') }}: {{ $worker->salary ? number_format((float) $worker->salary).' '.__('dashboard.sar') : '—' }}</li>
                            @if ($worker->agency)
                                <li><i class="ri-building-line me-1"></i>{{ $worker->agency->trans('name') }}</li>
                            @endif
                        </ul>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between font-size-12 mb-1">
                                <span>{{ __('recruitment.documents.completeness') }}</span>
                                <span dir="ltr">{{ $progress['uploaded'] }}/{{ $progress['required'] }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar {{ $progress['complete'] ? 'bg-success' : 'bg-warning' }}" style="width: {{ $progress['percent'] }}%"></div>
                            </div>
                            @unless ($progress['complete'])
                                <div class="text-danger font-size-11 mt-1 text-truncate" title="{{ implode('، ', $progress['missing']) }}">{{ __('recruitment.documents.missing') }}: {{ implode('، ', $progress['missing']) }}</div>
                            @endunless
                        </div>

                        <div class="mt-auto d-flex gap-2">
                            <a href="{{ route('admin.workers.cv.preview', $worker) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="ri-file-user-line me-1"></i>{{ __('recruitment.ready.cv') }}
                            </a>
                            @can('create', App\Models\RecruitmentOrder::class)
                                <a href="{{ route('admin.recruitment.create', ['worker' => $worker->id]) }}" class="btn btn-sm btn-primary flex-fill">
                                    <i class="ri-flight-takeoff-line me-1"></i>{{ __('recruitment.direct_order') }}
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body text-center text-muted py-5">{{ __('recruitment.ready.empty') }}</div></div>
            </div>
        @endforelse
    </div>

    {{ $workers->links() }}
@endsection
