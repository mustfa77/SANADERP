@extends('layouts.auth')

@section('title', __('auth.reset.title'))

@section('content')
    <h4 class="font-size-18 text-muted mt-2 text-center">{{ __('auth.reset.title') }}</h4>
    <p class="mb-5 text-center">{{ __('auth.reset.subtitle') }}</p>

    <form class="form-horizontal" method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-4">
            <label class="form-label" for="email">{{ __('auth.login.email') }}</label>
            <input type="email" name="email" id="email" dir="ltr" value="{{ old('email', $email) }}" required
                class="form-control @error('email') is-invalid @enderror">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @include('auth.partials.new-password', ['passwordLabel' => __('auth.reset.password'), 'confirmationLabel' => __('auth.reset.password_confirmation')])

        <div class="d-grid mt-4">
            <button class="btn btn-primary waves-effect waves-light" type="submit">{{ __('auth.reset.submit') }}</button>
        </div>
    </form>
@endsection
