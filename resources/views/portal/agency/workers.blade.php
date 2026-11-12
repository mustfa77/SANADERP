@extends('layouts.portal')

@section('title', __('cv.portal.title'))
@section('portal_title', __('portal.agency_title'))
@section('portal_nav') @include('portal.agency._nav') @endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">{{ __('cv.portal.title') }}</h4>
                    <p class="text-muted mb-0">{{ __('cv.portal.hint') }}</p>
                </div>
                <form method="GET" action="{{ route('agency.workers.index') }}" class="d-flex gap-2">
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('cv.portal.search') }}">
                    <button type="submit" class="btn btn-outline-primary"><i class="ri-search-line"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-centered table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('cv.fields.cv_reference_no') }}</th>
                            <th>{{ __('cv.fields.name') }}</th>
                            <th>{{ __('cv.fields.nationality') }}</th>
                            <th>{{ __('cv.fields.position') }}</th>
                            <th>{{ __('cv.portal.cv_status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workers as $worker)
                            <tr>
                                <td dir="ltr" class="text-start">{{ $worker->cv_reference_no ?? '—' }}</td>
                                <td class="fw-semibold">{{ $worker->name_en }}</td>
                                <td>{{ $worker->country?->trans('nationality') }}</td>
                                <td>{{ $worker->profession?->trans('name') }}</td>
                                <td>
                                    @if ($worker->cv_updated_at)
                                        <span class="badge bg-success-subtle text-success">{{ __('cv.portal.filled', ['date' => $worker->cv_updated_at->format('Y-m-d')]) }}</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">{{ __('cv.portal.missing') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('agency.workers.edit', $worker->id) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line me-1"></i>{{ __('cv.edit') }}</a>
                                    <a href="{{ route('agency.workers.preview', $worker->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success" title="{{ __('cv.print') }}"><i class="ri-file-user-line"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $workers->links() }}</div>
        </div>
    </div>
@endsection
