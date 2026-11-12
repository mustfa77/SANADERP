@extends('layouts.app')

@section('title', __('branches.create'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('branches.create'), 'parent' => __('branches.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.branches.store') }}" novalidate>
        @include('admin.branches._form')
    </form>
@endsection
