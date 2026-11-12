@extends('layouts.portal')

@section('title', __('portal.candidates'))
@section('portal_title', __('portal.agency_title'))
@section('portal_nav') @include('portal.agency._nav') @endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">{{ __('portal.candidates') }}</h4>
            <div class="table-responsive">
                <table class="table table-centered table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('portal.fields.name') }}</th>
                            <th>{{ __('portal.fields.nationality') }}</th>
                            <th>{{ __('portal.fields.passport_number') }}</th>
                            <th>{{ __('portal.fields.status') }}</th>
                            <th>{{ __('portal.fields.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($candidates as $candidate)
                            <tr>
                                <td>{{ $candidate->trans('name') }}</td>
                                <td>{{ $candidate->nationality }}</td>
                                <td><span dir="ltr">{{ $candidate->passport_number }}</span></td>
                                <td>{{ $candidate->status }}</td>
                                <td><span dir="ltr">{{ $candidate->created_at->format('Y-m-d') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $candidates->links() }}</div>
        </div>
    </div>
@endsection
