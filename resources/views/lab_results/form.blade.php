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
                    @include('alerts.errors')
                    @error('analytes')
                        <div class="alert alert-danger text-white" role="alert">{{ $message }}</div>
                    @enderror

                    <form method="post" autocomplete="off"
                        action="{{ $labResult->exists ? route('patient.lab-results.update', [$patient, $labResult]) : route('patient.lab-results.store', $patient) }}">
                        @csrf
                        @if($labResult->exists)
                            @method('put')
                        @endif

                        <div class="row">
                            <x-form-field name="taken_at" type="date" :label="__('Fecha de toma de muestra')" :value="$labResult->taken_at?->toDateString()"
                                col="col-md-3" max="{{ now()->toDateString() }}" required />
                            <x-consultation-select :consultations="$consultations" :selected="$labResult->diagnosis_id" />
                        </div>

                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mb-3 mt-2">{{ __('Metabolismo de la glucosa') }}</h6>
                        <div class="row">
                            @foreach (['fasting_glucose', 'fasting_insulin', 'hba1c'] as $field)
                                <x-form-field :name="$field" :label="__(\App\Models\LabResult::ANALYTES[$field]['label'])" :unit="\App\Models\LabResult::ANALYTES[$field]['unit']"
                                    :value="$labResult->{$field}" col="col-md-4" inputmode="decimal" />
                            @endforeach
                        </div>

                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mb-3 mt-2">{{ __('Perfil lipídico') }}</h6>
                        <div class="row">
                            @foreach (['total_cholesterol', 'hdl', 'ldl', 'triglycerides'] as $field)
                                <x-form-field :name="$field" :label="__(\App\Models\LabResult::ANALYTES[$field]['label'])" :unit="\App\Models\LabResult::ANALYTES[$field]['unit']"
                                    :value="$labResult->{$field}" col="col-md-3" inputmode="decimal" />
                            @endforeach
                        </div>
                        <p class="text-xs text-secondary mt-n2">
                            {{ __('El sistema calcula HOMA-IR (glicemia e insulina), índice TyG (triglicéridos y glicemia) y la relación TG/HDL.') }}
                        </p>

                        <div class="row">
                            <x-form-textarea name="notes" :label="__('Observaciones')" :value="$labResult->notes" col="col-12" rows="2"
                                placeholder="{{ __('Ej: laboratorio, condiciones de la toma de muestra…') }}" />
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('patient.lab-results.index', $patient) }}" class="btn btn-outline-secondary mb-0">{{ __('Cancelar') }}</a>
                            <button type="submit" class="btn btn-primary mb-0">{{ __('Guardar examen') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
