@extends('layouts.app')

@php
    $singular = __('reference.resources.stages.singular');
    $title = $stage->exists ? __('reference.edit', ['item' => $singular]) : __('reference.add', ['item' => $singular]);
@endphp

@section('title', $title)

@section('content')
    @include('layouts.partials.page-title', ['title' => $title, 'parent' => $country->trans('name')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ $stage->exists ? route('admin.countries.stages.update', [$country, $stage]) : route('admin.countries.stages.store', $country) }}" novalidate>
        @csrf
        @if ($stage->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-body">
                <div class="row">
                    @foreach (['ar' => 'rtl', 'en' => 'ltr'] as $locale => $direction)
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="name_{{ $locale }}">{{ __('reference.field_in_language.'.$locale, ['field' => __('reference.fields.name')]) }} <span class="text-danger">*</span></label>
                            <input type="text" name="name_{{ $locale }}" id="name_{{ $locale }}" dir="{{ $direction }}" value="{{ old('name_'.$locale, $stage->{'name_'.$locale}) }}"
                                class="form-control @error('name_'.$locale) is-invalid @enderror">
                            @error('name_'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="max_days">{{ __('reference.fields.max_days') }}</label>
                        <input type="number" name="max_days" id="max_days" min="1" max="365" dir="ltr" value="{{ old('max_days', $stage->max_days) }}"
                            class="form-control @error('max_days') is-invalid @enderror">
                        <div class="form-text">{{ __('reference.max_days_hint') }}</div>
                        @error('max_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-center">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch mt-md-3">
                            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $stage->is_active))>
                            <label class="form-check-label" for="is_active">{{ __('reference.fields.is_active') }}</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
                    <a href="{{ route('admin.countries.show', [$country, 'tab' => 'stages']) }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                </div>
            </div>
        </div>
    </form>
@endsection
