@extends('layouts.app')

@section('title', __('documents.demo.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('documents.demo.title')])

    <div class="alert alert-warning"><i class="ri-flask-line me-1"></i>{{ __('documents.demo.notice') }}</div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.documents.demo') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="country">{{ __('reference.fields.country_id') }}</label>
                    <select name="country" id="country" class="form-select" onchange="this.form.submit()">
                        @foreach ($countries as $item)
                            <option value="{{ $item->id }}" @selected($country?->id === $item->id)>{{ $item->trans('name') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="profession">{{ __('reference.fields.profession_id') }}</label>
                    <select name="profession" id="profession" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('reference.all_professions') }}</option>
                        @foreach ($professions as $item)
                            <option value="{{ $item->id }}" @selected($profession?->id === $item->id)>{{ $item->trans('name') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="authority">{{ __('reference.fields.authority_id') }}</label>
                    <select name="authority" id="authority" class="form-select" onchange="this.form.submit()">
                        @forelse ($authorities as $item)
                            <option value="{{ $item->id }}" @selected($authority?->id === $item->id)>{{ $item->trans('name') }}</option>
                        @empty
                            <option value="">{{ __('documents.demo.no_authority') }}</option>
                        @endforelse
                    </select>
                </div>
            </form>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <p class="text-muted mb-0">{{ __('documents.demo.owner', ['name' => $owner->trans('name')]) }}</p>
                @if ($authority)
                    <x-documents.print-button :owner="$owner" :authority="$authority" />
                @endif
            </div>
        </div>
    </div>

    <x-documents.checklist :owner="$owner" :country="$country?->id" :profession="$profession?->id" />
@endsection
