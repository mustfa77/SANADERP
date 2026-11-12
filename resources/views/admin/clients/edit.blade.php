@extends('layouts.app')

@section('title', __('clients.edit'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('clients.edit'), 'parent' => __('clients.title')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ route('admin.clients.update', $client) }}" novalidate>
        @method('PUT')
        @include('admin.clients._form')
    </form>
@endsection
