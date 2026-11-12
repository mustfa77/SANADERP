@extends('layouts.portal')

@section('title', __('portal.documents'))
@section('portal_title', __('portal.agency_title'))
@section('portal_nav') @include('portal.agency._nav') @endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">{{ __('portal.documents') }}</h4>
            <div class="table-responsive">
                <table class="table table-centered table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('portal.fields.candidate') }}</th>
                            <th>{{ __('portal.fields.type') }}</th>
                            <th>{{ __('portal.fields.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $document)
                            <tr>
                                <td>{{ $document->documentable?->trans('name') ?? '—' }}</td>
                                <td>{{ $document->displayName() }}</td>
                                <td><span dir="ltr">{{ $document->created_at->format('Y-m-d') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">{{ __('common.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $documents->links() }}</div>
        </div>
    </div>
@endsection
