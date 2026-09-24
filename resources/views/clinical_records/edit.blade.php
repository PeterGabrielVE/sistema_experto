@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Ficha clínica',
    'activePage' => 'patient',
    'activeNav' => '',
])

@section('content')
    <div class="panel-header panel-header-sm">
    </div>
    <div class="content">
        <div class="row">
            <div class="col-xl-12 order-xl-1">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h3 class="mb-0">
                                    {{ $record->exists ? __('Editar ficha clínica') : __('Registrar ficha clínica') }}
                                </h3>
                                <p class="text-muted mb-0">{{ $patient->fullName() }} · {{ __('RUT') }} {{ $patient->rut }}</p>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ $record->exists ? route('patient.clinical-record.show', $patient) : route('patient.index') }}" class="btn btn-primary btn-round">{{ __('Volver') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('alerts.success')
                        @include('alerts.errors')

                        <form method="post" action="{{ route('patient.clinical-record.update', $patient) }}" autocomplete="off">
                            @csrf
                            @method('put')

                            <h6 class="heading-small text-muted mb-3">{{ __('Motivo de consulta') }}</h6>
                            <div class="row">
                                <x-form-textarea name="consultation_reason" :label="__('Motivo de consulta')" :value="$record->consultation_reason" col="col-12" rows="3" required />
                            </div>

                            <h6 class="heading-small text-muted mb-3 mt-4">{{ __('Antecedentes mórbidos') }}</h6>
                            <div class="row mb-2">
                                @foreach ($conditions as $field => $label)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1"
                                                    @checked(old($field, $record->{$field}))>
                                                <span class="form-check-sign"></span>
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row">
                                <x-form-textarea name="other_conditions" :label="__('Otras patologías')" :value="$record->other_conditions" />
                                <x-form-textarea name="family_history" :label="__('Antecedentes familiares')" :value="$record->family_history" placeholder="Ej: madre con diabetes tipo 2" />
                                <x-form-textarea name="medications" :label="__('Fármacos en uso')" :value="$record->medications" placeholder="Ej: metformina 850 mg c/12 h" />
                                <x-form-textarea name="food_allergies" :label="__('Alergias o intolerancias alimentarias')" :value="$record->food_allergies" />
                            </div>

                            <h6 class="heading-small text-muted mb-3 mt-4">{{ __('Hábitos') }}</h6>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label class="form-control-label" for="input-smoking">{{ __('Tabaco') }}</label>
                                    <x-select name="smoking" :options="$smokingOptions" :selected="$record->smoking?->value" class="form-control" id="input-smoking" />
                                    @include('alerts.feedback', ['field' => 'smoking'])
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label" for="input-alcohol">{{ __('Alcohol') }}</label>
                                    <x-select name="alcohol" :options="$alcoholOptions" :selected="$record->alcohol?->value" class="form-control" id="input-alcohol" />
                                    @include('alerts.feedback', ['field' => 'alcohol'])
                                </div>
                                <x-form-field name="sleep_hours" :label="__('Sueño')" unit="h/día" :value="$record->sleep_hours" col="col-md-3" inputmode="decimal" />
                                <x-form-field name="water_liters" :label="__('Agua')" unit="L/día" :value="$record->water_liters" col="col-md-3" inputmode="decimal" />
                            </div>

                            <h6 class="heading-small text-muted mb-3 mt-4">{{ __('Antropometría') }}</h6>
                            <div class="row">
                                <x-form-field name="waist_cm" :label="__('Circunferencia de cintura')" unit="cm" :value="$record->waist_cm" col="col-md-3" inputmode="decimal" />
                            </div>

                            <h6 class="heading-small text-muted mb-3 mt-4">{{ __('Exámenes de laboratorio') }}</h6>
                            <div class="row">
                                <x-form-field name="lab_date" type="date" :label="__('Fecha de exámenes')" :value="$record->lab_date?->toDateString()" col="col-md-3" max="{{ now()->toDateString() }}" />
                                <x-form-field name="fasting_glucose" :label="__('Glicemia en ayunas')" unit="mg/dL" :value="$record->fasting_glucose" col="col-md-3" inputmode="decimal" />
                                <x-form-field name="fasting_insulin" :label="__('Insulina basal')" unit="µU/mL" :value="$record->fasting_insulin" col="col-md-3" inputmode="decimal" />
                                <x-form-field name="hba1c" :label="__('HbA1c')" unit="%" :value="$record->hba1c" col="col-md-3" inputmode="decimal" />
                                <x-form-field name="total_cholesterol" :label="__('Colesterol total')" unit="mg/dL" :value="$record->total_cholesterol" col="col-md-3" inputmode="decimal" />
                                <x-form-field name="hdl" :label="__('HDL')" unit="mg/dL" :value="$record->hdl" col="col-md-3" inputmode="decimal" />
                                <x-form-field name="ldl" :label="__('LDL')" unit="mg/dL" :value="$record->ldl" col="col-md-3" inputmode="decimal" />
                                <x-form-field name="triglycerides" :label="__('Triglicéridos')" unit="mg/dL" :value="$record->triglycerides" col="col-md-3" inputmode="decimal" />
                            </div>
                            <p class="text-muted small">
                                {{ __('Con glicemia e insulina basal el sistema calcula el HOMA-IR automáticamente.') }}
                            </p>

                            <h6 class="heading-small text-muted mb-3 mt-4">{{ __('Observaciones') }}</h6>
                            <div class="row">
                                <x-form-textarea name="notes" :label="__('Observaciones')" :value="$record->notes" col="col-12" rows="3" />
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-info mt-4">{{ __('Guardar ficha') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
