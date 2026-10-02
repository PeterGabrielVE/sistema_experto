@extends('layouts.app', [
    'namePage' => 'Exámenes de laboratorio',
    'activePage' => 'patient',
])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex align-items-center">
                        <div>
                            <h6 class="mb-0">{{ $labResult->exists ? __('Editar examen') : __('Registrar examen') }}</h6>
                            <p class="text-sm text-secondary mb-0">{{ $patient->fullName() }} · {{ __('RUT') }} {{ $patient->rut }}</p>
                        </div>
                        <div class="ms-auto">
                            <a href="{{ route('patient.lab-results.index', $patient) }}" class="btn btn-outline-primary btn-sm mb-0">{{ __('Volver a exámenes') }}</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="lab-result-form" data-props="{{ json_encode([
                        'mode' => $labResult->exists ? 'edit' : 'create',
                        'action' => $labResult->exists ? route('patient.lab-results.update', [$patient, $labResult]) : route('patient.lab-results.store', $patient),
                        'cancelUrl' => route('patient.lab-results.index', $patient),
                        'analytes' => \App\Models\LabResult::ANALYTES,
                        'panels' => collect(\App\Models\LabResult::PANELS)->map(fn ($fields, $title) => ['title' => $title, 'fields' => $fields])->values(),
                        'thresholds' => [
                            'homaIr' => config('clinical.insulin_resistance.homa_ir'),
                            'tyg' => config('clinical.insulin_resistance.tyg'),
                            'tgHdl' => config('clinical.insulin_resistance.tg_hdl'),
                        ],
                        // A list, not an object: JS would reorder the numeric ids.
                        'consultations' => collect($consultations)->map(fn ($label, $id) => ['value' => $id, 'label' => $label])->values(),
                        'minDate' => $patient->birthdate?->toDateString(),
                        'maxDate' => now()->toDateString(),
                        'labResult' => ['taken_at' => $labResult->taken_at?->toDateString()] + $labResult->only(\App\Models\LabResult::CLINICAL_FIELDS),
                    ]) }}"></div>
                    <noscript>
                        <div class="alert alert-warning text-white">{{ __('Active JavaScript para registrar exámenes.') }}</div>
                    </noscript>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @vite('resources/js/lab-result-form.js')
@endpush
