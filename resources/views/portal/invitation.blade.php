{{-- Public, from the welcome mail (US-6.3/AC-3). Nothing of the patient on this page. --}}
@extends('layouts.app', [
    'namePage' => 'Portal de pacientes',
    'activePage' => 'portal',
    'forPatients' => true,
])

@section('content')
    <div class="col-xl-6 col-lg-8 col-md-10">
        <div class="card z-index-0">
            <div class="card-header text-center pt-4 pb-0">
                <h5>{{ __('Cree su contraseña del portal de pacientes') }}</h5>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('portal.invitation.accept', $token) }}" autocomplete="off">
                    @csrf
                    <input type="hidden" name="terms_version" value="{{ $termsVersion }}">

                    <div class="mb-3">
                        <label class="form-label" for="password">{{ __('Contraseña') }}</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                            class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}">
                        <small class="text-muted">{{ __('Al menos 8 caracteres, con letras y números.') }}</small>
                        @include('alerts.feedback', ['field' => 'password'])
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">{{ __('Repita la contraseña') }}</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control">
                    </div>

                    <h6 class="mt-4">{{ __('Términos de uso y tratamiento de datos') }}</h6>
                    <div class="border border-radius-lg p-3 mb-2 text-sm" style="max-height: 50vh; overflow-y: auto;">
                        {!! $terms !!}
                    </div>
                    <p class="text-xs text-secondary">{{ __('Versión :version', ['version' => $termsVersion]) }}</p>
                    <div class="form-check">
                        <input class="form-check-input{{ $errors->has('terms') ? ' is-invalid' : '' }}" type="checkbox" name="terms" id="terms" value="1" required>
                        <label class="form-check-label" for="terms">{{ __('Acepto los términos de uso y el tratamiento de mis datos personales') }}</label>
                    </div>
                    @include('alerts.feedback', ['field' => 'terms'])

                    <button type="submit" class="btn btn-lg btn-primary w-100 mt-4 mb-0">{{ __('Crear mi cuenta') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
