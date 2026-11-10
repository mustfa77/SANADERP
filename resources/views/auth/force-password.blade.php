@extends('layouts.auth')

@section('title', __('auth.force.title'))

@section('content')
    <h4 class="font-size-18 text-muted mt-2 text-center">{{ __('auth.force.title') }}</h4>
    <p class="mb-5 text-center">{{ __('auth.force.subtitle') }}</p>

    <form method="POST" action="{{ route('password.force.update') }}" novalidate>
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="form-label" for="current_password">{{ __('auth.force.current_password') }}</label>
            <input type="password" name="current_password" id="current_password" dir="ltr" required autocomplete="current-password"
                class="form-control @error('current_password') is-invalid @enderror">
            @error('current_password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @include('auth.partials.new-password', ['passwordLabel' => __('auth.reset.password'), 'confirmationLabel' => __('auth.reset.password_confirmation')])

        <div class="d-grid">
            <button class="btn btn-primary waves-effect waves-light" type="submit">{{ __('auth.force.submit') }}</button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center mt-4">
        @csrf
        <button type="submit" class="btn btn-link p-0 text-danger">{{ __('auth.logout') }}</button>
    </form>
@endsection
