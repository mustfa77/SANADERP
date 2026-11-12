@extends('layouts.app')

@section('title', __('cv.edit'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('cv.edit'), 'parent' => __('workers.title')])

    <x-alerts :flash="false" />

    @include('workers._cv-form', [
        'action' => route('admin.workers.cv.update', $worker),
        'cancelUrl' => route('admin.workers.show', [$worker, 'tab' => 'cv']),
        'photoUrl' => $worker->photo_path ? route('admin.workers.photo', $worker) : null,
        'fullPhotoUrl' => $worker->full_photo_path ? route('admin.workers.full-photo', $worker) : null,
    ])
@endsection
