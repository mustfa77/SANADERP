@extends('layouts.auth')

@section('title', __('auth.two_factor.title'))

@section('content')
    <h4 class="font-size-18 text-muted mt-2 text-center">{{ __('auth.two_factor.title') }}</h4>
    <p class="mb-5 text-center">{{ __('auth.two_factor.subtitle') }}</p>

    <x-alerts :summary="false" />

    <form method="POST" action="{{ route('two-factor.verify') }}" novalidate>
        @csrf
        <div class="mb-4">
            <label class="form-label" for="code">{{ __('auth.two_factor.code') }}</label>
            <input type="text" name="code" id="code" dir="ltr" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required autofocus
                class="form-control text-center font-size-20 @error('code') is-invalid @enderror">
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="d-grid">
            <button class="btn btn-primary waves-effect waves-light" type="submit">{{ __('auth.two_factor.submit') }}</button>
        </div>
    </form>

    <div class="d-flex justify-content-between mt-4">
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button type="submit" class="btn btn-link p-0 text-muted">{{ __('auth.two_factor.resend') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link p-0 text-danger">{{ __('auth.logout') }}</button>
        </form>
    </div>
@endsection
