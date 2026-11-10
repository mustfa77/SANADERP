@extends('layouts.app')

@section('title', __('branches.edit'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('branches.edit'), 'parent' => __('branches.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.branches.update', $branch) }}" novalidate>
        @method('PUT')
        @include('admin.branches._form')
    </form>
@endsection
