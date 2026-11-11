@extends('layouts.app')

@php
    $singular = __('reference.resources.document_types.singular');
    $title = $documentType->exists ? __('reference.edit', ['item' => $singular]) : __('reference.add', ['item' => $singular]);
    $backUrl = $country ? route('admin.countries.show', [$country, 'tab' => 'documents']) : route('admin.document-types.index');
@endphp

@section('title', $title)

@section('content')
    @include('layouts.partials.page-title', ['title' => $title, 'parent' => $country?->trans('name') ?? __('reference.global_document_types')])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ $documentType->exists ? route('admin.document-types.update', $documentType) : route('admin.document-types.store') }}" novalidate>
        @csrf
        @if ($documentType->exists)
            @method('PUT')
        @elseif ($country)
            <input type="hidden" name="country_id" value="{{ $country->id }}">
        @endif

        <div class="card">
            <div class="card-body">
                <div class="alert alert-light border mb-4">
                    <i class="ri-earth-line me-1"></i>{{ __('reference.fields.country_id') }}:
                    <strong>{{ $country?->trans('name') ?? __('reference.all_countries') }}</strong>
                </div>

                <div class="row">
                    @foreach (['ar' => 'rtl', 'en' => 'ltr'] as $locale => $direction)
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="name_{{ $locale }}">{{ __('reference.field_in_language.'.$locale, ['field' => __('reference.fields.name')]) }} <span class="text-danger">*</span></label>
                            <input type="text" name="name_{{ $locale }}" id="name_{{ $locale }}" dir="{{ $direction }}" value="{{ old('name_'.$locale, $documentType->{'name_'.$locale}) }}"
                                class="form-control @error('name_'.$locale) is-invalid @enderror">
                            @error('name_'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endforeach

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="profession_id">{{ __('reference.fields.profession_id') }}</label>
                        <select name="profession_id" id="profession_id" class="form-select @error('profession_id') is-invalid @enderror">
                            <option value="">{{ __('reference.all_professions') }}</option>
                            @foreach ($professions as $profession)
                                <option value="{{ $profession->id }}" @selected((string) old('profession_id', $documentType->profession_id) === (string) $profession->id)>{{ $profession->trans('name') }}</option>
                            @endforeach
                        </select>
                        @error('profession_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="authority_id">{{ __('reference.fields.authority_id') }}</label>
                        <select name="authority_id" id="authority_id" class="form-select @error('authority_id') is-invalid @enderror">
                            <option value="">{{ __('common.none') }}</option>
                            @foreach ($authorities as $authority)
                                <option value="{{ $authority->id }}" @selected((string) old('authority_id', $documentType->authority_id) === (string) $authority->id)>{{ $authority->trans('name') }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('reference.authority_hint') }}</div>
                        @error('authority_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @foreach (['is_required', 'include_in_authority_file', 'is_active'] as $flag)
                        <div class="col-md-4 mb-3">
                            <input type="hidden" name="{{ $flag }}" value="0">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="{{ $flag }}" value="1" id="{{ $flag }}" class="form-check-input" @checked(old($flag, $documentType->{$flag}))>
                                <label class="form-check-label" for="{{ $flag }}">{{ __('reference.fields.'.$flag) }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
                    <a href="{{ $backUrl }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                </div>
            </div>
        </div>
    </form>
@endsection
