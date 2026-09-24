@extends('layouts.app', [
    'namePage' => 'Ingresar',
    'activePage' => 'login',
])

@section('content')
    <div class="col-xl-4 col-lg-5 col-md-7">
        <div class="card z-index-0">
            <div class="card-header text-center pt-4 pb-0">
                <img src="{{ asset('assets/img/sauce-logo.png') }}" alt="El Sauce" width="110" height="110">
                <h5 class="mt-3">{{ __('Ingresar') }}</h5>
                @include('alerts.migrations_check')
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success text-white text-sm" role="alert">{{ session('status') }}</div>
                @endif

                <form role="form" method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">{{ __('Correo') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                            class="form-control form-control-lg{{ $errors->has('email') ? ' is-invalid' : '' }}"
                            placeholder="nombre@correo.cl" autocomplete="username" required autofocus>
                        @include('alerts.feedback', ['field' => 'email'])
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">{{ __('Contraseña') }}</label>
                        <input id="password" type="password" name="password"
                            class="form-control form-control-lg{{ $errors->has('password') ? ' is-invalid' : '' }}"
                            autocomplete="current-password" required>
                        @include('alerts.feedback', ['field' => 'password'])
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
                        <label class="form-check-label" for="remember">{{ __('Recordarme') }}</label>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-lg btn-primary w-100 mt-4 mb-0">{{ __('Ingresar') }}</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center pt-0 px-lg-2 px-1">
                <p class="mb-2 text-sm">
                    <a href="{{ route('password.request') }}" class="text-primary font-weight-bold">{{ __('¿Olvidaste tu contraseña?') }}</a>
                </p>
            </div>
        </div>
    </div>
@endsection
