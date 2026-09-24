@extends('layouts.app', [
    'namePage' => 'Editar paciente',
    'activePage' => 'patient',
])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex align-items-center">
                        <div>
                            <h6 class="mb-0">{{ __('Editar paciente') }}</h6>
                            <p class="text-sm text-secondary mb-0">{{ $patient->fullName() }} · {{ __('RUT') }} {{ $patient->rut }}</p>
                        </div>
                        <div class="ms-auto">
                            @can('viewClinicalRecord', $patient)
                                <a href="{{ route('patient.clinical-record.show', $patient) }}" class="btn btn-outline-warning btn-sm mb-0">{{ __('Ficha clínica') }}</a>
                            @endcan
                            <a href="{{ route('patient.index') }}" class="btn btn-outline-primary btn-sm mb-0">{{ __('Volver a la lista') }}</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="patient-form" data-props="{{ json_encode([
                        'mode' => 'edit',
                        'action' => route('patient.update', $patient),
                        'cancelUrl' => route('patient.index'),
                        'genders' => $genders,
                        'patient' => (new \App\Http\Resources\PatientResource($patient))->resolve(),
                    ]) }}"></div>
                    <noscript>
                        <div class="alert alert-warning text-white">{{ __('Active JavaScript para editar pacientes.') }}</div>
                    </noscript>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @vite('resources/js/patient-form.js')
@endpush
