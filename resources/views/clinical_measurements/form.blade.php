@extends('layouts.app', [
    'namePage' => 'Mediciones clínicas',
    'activePage' => 'patient',
])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex align-items-center">
                        <div>
                            <h6 class="mb-0">{{ $measurement->exists ? __('Editar medición') : __('Registrar medición') }}</h6>
                            <p class="text-sm text-secondary mb-0">{{ $patient->fullName() }} · {{ __('RUT') }} {{ $patient->rut }}</p>
                        </div>
                        <div class="ms-auto">
                            <a href="{{ route('patient.measurements.index', $patient) }}" class="btn btn-outline-primary btn-sm mb-0">{{ __('Volver al registro') }}</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @include('alerts.errors')
                    @error('measurements')
                        <div class="alert alert-danger text-white" role="alert">{{ $message }}</div>
                    @enderror

                    <form method="post" autocomplete="off"
                        action="{{ $measurement->exists ? route('patient.measurements.update', [$patient, $measurement]) : route('patient.measurements.store', $patient) }}">
                        @csrf
                        @if($measurement->exists)
                            @method('put')
                        @endif

                        <div class="row">
                            <x-form-field name="measured_at" type="date" :label="__('Fecha de medición')" :value="$measurement->measured_at?->toDateString()"
                                col="col-md-3" max="{{ now()->toDateString() }}" required />
                        </div>

                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mb-3 mt-2">{{ __('Antropometría') }}</h6>
                        <div class="row">
                            <x-form-field name="weight_kg" :label="__('Peso')" unit="kg" :value="$measurement->weight_kg" col="col-md-4 col-lg" inputmode="decimal" />
                            <x-form-field name="height_cm" :label="__('Talla')" unit="cm" :value="$measurement->height_cm" col="col-md-4 col-lg" inputmode="decimal" />
                            <x-form-field name="waist_cm" :label="__('Cintura')" unit="cm" :value="$measurement->waist_cm" col="col-md-4 col-lg" inputmode="decimal" />
                            <x-form-field name="hip_cm" :label="__('Cadera')" unit="cm" :value="$measurement->hip_cm" col="col-md-4 col-lg" inputmode="decimal" />
                            <x-form-field name="body_fat_pct" :label="__('Grasa corporal')" unit="%" :value="$measurement->body_fat_pct" col="col-md-4 col-lg" inputmode="decimal" />
                        </div>
                        <p class="text-xs text-secondary mt-n2">
                            {{ __('Con peso y talla se calcula el IMC; con cintura, talla y cadera, los índices cintura/talla y cintura/cadera.') }}
                        </p>

                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mb-3 mt-4">{{ __('Signos vitales y glicemia') }}</h6>
                        <div class="row">
                            <x-form-field name="systolic_bp" :label="__('Presión sistólica')" unit="mmHg" :value="$measurement->systolic_bp" col="col-md-3" inputmode="numeric" />
                            <x-form-field name="diastolic_bp" :label="__('Presión diastólica')" unit="mmHg" :value="$measurement->diastolic_bp" col="col-md-3" inputmode="numeric" />
                            <x-form-field name="heart_rate" :label="__('Frecuencia cardíaca')" unit="lpm" :value="$measurement->heart_rate" col="col-md-3" inputmode="numeric" />
                            <x-form-field name="capillary_glucose" :label="__('Glicemia capilar')" unit="mg/dL" :value="$measurement->capillary_glucose" col="col-md-3" inputmode="decimal" />
                        </div>

                        <div class="row">
                            <x-form-textarea name="notes" :label="__('Observaciones')" :value="$measurement->notes" col="col-12" rows="2"
                                placeholder="{{ __('Ej: medición post prandial, paciente en ayunas de 8 h…') }}" />
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('patient.measurements.index', $patient) }}" class="btn btn-outline-secondary mb-0">{{ __('Cancelar') }}</a>
                            <button type="submit" class="btn btn-primary mb-0">{{ __('Guardar medición') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
