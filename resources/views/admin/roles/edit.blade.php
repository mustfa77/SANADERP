@extends('layouts.app')

@section('title', __('roles.edit'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('roles.edit'), 'parent' => __('roles.title')])

    <x-alerts :flash="false" />

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.roles.permissions.edit', $role) }}" class="btn btn-outline-primary">
            <i class="ri-key-2-line me-1"></i>{{ __('roles.permissions') }}
        </a>
    </div>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" novalidate>
        @method('PUT')
        @include('admin.roles._form')
    </form>
@endsection
