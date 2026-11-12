@extends('layouts.portal')

@section('title', __('cv.edit'))
@section('portal_title', __('portal.agency_title'))
@section('portal_nav') @include('portal.agency._nav') @endsection

@section('content')
    <x-alerts />

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('agency.workers.preview', $worker->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-success"><i class="ri-file-user-line me-1"></i>{{ __('cv.print') }}</a>
    </div>

    @include('workers._cv-form', [
        'action' => route('agency.workers.update', $worker->id),
        'cancelUrl' => route('agency.workers.index'),
        'photoUrl' => $worker->photo_path ? route('agency.workers.photo', [$worker->id, 'face']) : null,
        'fullPhotoUrl' => $worker->full_photo_path ? route('agency.workers.photo', [$worker->id, 'full']) : null,
    ])
@endsection
