@extends('layouts.app', [
    'namePage' => 'Nuevo paciente',
    'activePage' => 'patient',
])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex align-items-center">
                        <div>
                            <h6 class="mb-0">{{ __('Registrar paciente') }}</h6>
                            <p class="text-sm text-secondary mb-0">{{ __('Después podrá completar su ficha clínica.') }}</p>
                        </div>
                        <a href="{{ route('patient.index') }}" class="btn btn-outline-primary btn-sm ms-auto mb-0">{{ __('Volver a la lista') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="patient-form" data-props="{{ json_encode([
                        'mode' => 'create',
                        'action' => route('patient.store'),
                        'cancelUrl' => route('patient.index'),
                        'genders' => $genders,
                    ]) }}"></div>
                    <noscript>
                        <div class="alert alert-warning text-white">{{ __('Active JavaScript para registrar pacientes.') }}</div>
                    </noscript>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @vite('resources/js/patient-form.js')
@endpush
