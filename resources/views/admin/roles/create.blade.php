@extends('layouts.app')

@section('title', __('roles.create'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('roles.create'), 'parent' => __('roles.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.roles.store') }}" novalidate>
        @include('admin.roles._form')
    </form>
@endsection
