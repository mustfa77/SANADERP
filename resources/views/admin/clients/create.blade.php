@extends('layouts.app')

@section('title', __('clients.create'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('clients.create'), 'parent' => __('clients.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.clients.store') }}" novalidate>
        @include('admin.clients._form')
    </form>
@endsection
