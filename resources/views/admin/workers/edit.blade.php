@extends('layouts.app')

@section('title', __('workers.edit'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('workers.edit'), 'parent' => __('workers.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.workers.update', $worker) }}" enctype="multipart/form-data" novalidate>
        @method('PUT')
        @include('admin.workers._form')
    </form>
@endsection
