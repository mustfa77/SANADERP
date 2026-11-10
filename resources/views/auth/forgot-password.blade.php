@extends('layouts.auth')

@section('title', __('auth.forgot.title'))

@section('content')
    <h4 class="font-size-18 text-muted mt-2 text-center">{{ __('auth.forgot.title') }}</h4>
    <p class="mb-5 text-center">{{ __('auth.forgot.subtitle', ['app' => $site->brand()]) }}</p>

    <x-alerts :summary="false" />

    <form class="form-horizontal" method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="row">
            <div class="col-md-12">
                @unless (session('success'))
                    <div class="alert alert-warning alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('common.close') }}"></button>
                        {!! __('auth.forgot.hint') !!}
                    </div>
                @endunless

                <div class="mt-4">
                    <label class="form-label" for="email">{{ __('auth.login.email') }}</label>
                    <input type="email" name="email" id="email" dir="ltr" value="{{ old('email') }}" required autofocus
                        class="form-control @error('email') is-invalid @enderror" placeholder="{{ __('auth.login.email_placeholder') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-grid mt-4">
                    <button class="btn btn-primary waves-effect waves-light" type="submit">{{ __('auth.forgot.submit') }}</button>
                </div>
                <div class="mt-4 text-center">
                    <a href="{{ route('login') }}" class="text-muted"><i class="mdi mdi-arrow-left"></i> {{ __('auth.forgot.back') }}</a>
                </div>
            </div>
        </div>
    </form>
@endsection
