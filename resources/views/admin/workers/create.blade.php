@extends('layouts.app')

@section('title', __('workers.create'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('workers.create'), 'parent' => __('workers.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.workers.store') }}" enctype="multipart/form-data" novalidate>
        @include('admin.workers._form')
    </form>
@endsection
