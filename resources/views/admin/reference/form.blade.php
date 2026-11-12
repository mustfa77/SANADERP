@extends('layouts.app')

@php
    $title = $record->exists ? __('reference.edit', ['item' => $resource->singular()]) : __('reference.add', ['item' => $resource->singular()]);
@endphp

@section('title', $title)

@section('content')
    @include('layouts.partials.page-title', ['title' => $title, 'parent' => $resource->title()])

    <x-alerts :flash="false" />

    <form method="POST" action="{{ $record->exists ? route($resource->routeName('update'), $record) : route($resource->routeName('store')) }}" novalidate>
        @csrf
        @if ($record->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-body">
                <div class="row">
                    @foreach ($resource->fields as $name => $field)
                        @if ($field['translatable'] ?? false)
                            @foreach (['ar' => 'rtl', 'en' => 'ltr'] as $locale => $direction)
                                <x-reference.field :resource="$resource" :record="$record" :name="$name.'_'.$locale" :field="$field"
                                    :label="$resource->fieldLabel($name, $locale)" :dir="$direction" />
                            @endforeach
                        @else
                            <x-reference.field :resource="$resource" :record="$record" :name="$name" :field="$field"
                                :label="$resource->fieldLabel($name)" :dir="$field['dir'] ?? null" />
                        @endif
                    @endforeach

                    <div class="col-12 mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input @error('is_active') is-invalid @enderror"
                                @checked(old('is_active', $record->is_active))>
                            <label class="form-check-label" for="is_active">{{ __('reference.fields.is_active') }}</label>
                            @error('is_active') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="ri-save-line align-middle me-1"></i> {{ __('common.save') }}</button>
                    <a href="{{ route($resource->routeName('index')) }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                </div>
            </div>
        </div>
    </form>
@endsection
