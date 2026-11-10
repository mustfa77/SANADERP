@extends('layouts.app')

@section('title', __('users.create'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('users.create'), 'parent' => __('users.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" novalidate>
        @include('admin.users._form')
    </form>
@endsection
