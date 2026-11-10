@extends('layouts.app')

@section('title', __('users.edit'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('users.edit'), 'parent' => __('users.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data" novalidate>
        @method('PUT')
        @include('admin.users._form')
    </form>
@endsection
