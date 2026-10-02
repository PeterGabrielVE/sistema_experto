@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Ficha clínica',
    'activePage' => 'patient',
    'activeNav' => '',
])

@php
    $value = fn ($v, $unit = '') => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',').($unit ? ' '.$unit : '');
@endphp

@section('content')
    <div class="panel-header panel-header-sm">
    </div>
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h3 class="mb-0">{{ __('Ficha clínica') }}</h3>
                                <p class="text-muted mb-0">
                                    {{ $patient->fullName() }} · {{ __('RUT') }} {{ $patient->rut }}
                                    @if($patient->age !== null)
                                        · {{ $patient->age }} {{ __('años') }}
                                    @endif
                                    · {{ $patient->genderLabel() }}
                                    @if($patient->email)
                                        · {{ $patient->email }}
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-5 text-right">
                                @can('updateClinicalRecord', $patient)
                                    <a href="{{ route('patient.clinical-record.edit', $patient) }}" class="btn btn-info btn-round">{{ __('Editar ficha') }}</a>
                                @endcan
                                <a href="{{ route('patient.measurements.index', $patient) }}" class="btn btn-warning btn-round">{{ __('Mediciones') }}</a>
                                <a href="{{ route('patient.lab-results.index', $patient) }}" class="btn btn-danger btn-round">{{ __('Exámenes') }}</a>
                                <a href="{{ route('diagnosis.all', $patient) }}" class="btn btn-success btn-round">{{ __('Consultas') }}</a>
                                <a href="{{ route('patient.index') }}" class="btn btn-primary btn-round">{{ __('Volver') }}</a>
                            </div>
                        </div>
                        @include('alerts.success')
                    </div>
                    <div class="card-body">
                        <h6 class="heading-small text-muted">{{ __('Motivo de consulta') }}</h6>
                        <p style="white-space: pre-line">{{ $record->consultation_reason }}</p>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6 class="heading-small text-muted">{{ __('Antecedentes mórbidos') }}</h6>
                                <p>
                                    @forelse ($record->conditions() as $condition)
                                        <span class="badge badge-warning">{{ $condition }}</span>
                                    @empty
                                        <span class="text-muted">{{ __('Sin patologías registradas') }}</span>
                                    @endforelse
                                </p>
                                <dl>
                                    <dt>{{ __('Otras patologías') }}</dt><dd>{{ $record->other_conditions ?: '—' }}</dd>
                                    <dt>{{ __('Antecedentes familiares') }}</dt><dd>{{ $record->family_history ?: '—' }}</dd>
                                    <dt>{{ __('Fármacos en uso') }}</dt><dd>{{ $record->medications ?: '—' }}</dd>
                                    <dt>{{ __('Alergias o intolerancias alimentarias') }}</dt><dd>{{ $record->food_allergies ?: '—' }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="heading-small text-muted">{{ __('Hábitos y antropometría') }}</h6>
                                <dl>
                                    <dt>{{ __('Tabaco') }}</dt><dd>{{ $record->smoking?->label() ?? '—' }}</dd>
                                    <dt>{{ __('Alcohol') }}</dt><dd>{{ $record->alcohol?->label() ?? '—' }}</dd>
                                    <dt>{{ __('Sueño') }}</dt><dd>{{ $value($record->sleep_hours, 'h/día') }}</dd>
                                    <dt>{{ __('Agua') }}</dt><dd>{{ $value($record->water_liters, 'L/día') }}</dd>
                                    <dt>{{ __('Circunferencia de cintura') }}</dt>
                                    <dd>
                                        @if($lastWaist)
                                            {{ $value($lastWaist->waist_cm, 'cm') }}
                                            <small class="text-muted">({{ __('medición del :date', ['date' => $lastWaist->measured_at->format('d/m/Y')]) }})</small>
                                        @else
                                            —
                                        @endif
                                        <a href="{{ route('patient.measurements.index', $patient) }}" class="small ml-1">{{ __('Ver mediciones') }}</a>
                                    </dd>
                                </dl>
                            </div>
                        </div>

                        <h6 class="heading-small text-muted mt-2">{{ __('Cuestionario FINDRISC') }}</h6>
                        <dl class="row">
                            @foreach (\App\Models\ClinicalRecord::FINDRISC_QUESTIONS as $field => $question)
                                <dt class="col-md-8 font-weight-normal">{{ __($question) }}</dt>
                                <dd class="col-md-4">{{ $record->{$field} === null ? '—' : ($record->{$field} ? __('Sí') : __('No')) }}</dd>
                            @endforeach
                            <dt class="col-md-8 font-weight-normal">{{ __('¿Algún familiar ha sido diagnosticado con diabetes?') }}</dt>
                            <dd class="col-md-4">{{ $record->family_history_diabetes?->label() ?? '—' }}</dd>
                        </dl>

                        <h6 class="heading-small text-muted mt-4">
                            {{ __('Último examen de laboratorio') }}
                            @if($labResult)
                                <small>({{ $labResult->taken_at->format('d/m/Y') }})</small>
                            @endif
                            <a href="{{ route('patient.lab-results.index', $patient) }}" class="small ml-2">{{ __('Ver historial de exámenes') }}</a>
                        </h6>
                        @if(! $labResult)
                            <p class="text-muted">
                                {{ __('Sin exámenes registrados.') }}
                                @can('updateClinicalRecord', $patient)
                                    <a href="{{ route('patient.lab-results.create', $patient) }}">{{ __('Registrar examen') }}</a>
                                @endcan
                            </p>
                        @else
                            <div class="row">
                                <div class="col-md-8">
                                    <table class="table table-sm">
                                        <tbody>
                                            @foreach (\App\Models\LabResult::ANALYTES as $field => $analyte)
                                                <tr>
                                                    <td>{{ __($analyte['label']) }}</td>
                                                    <td>@include('lab_results._value', ['result' => $labResult, 'field' => $field]) {{ $labResult->{$field} !== null ? $analyte['unit'] : '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <div class="card card-stats text-center p-3">
                                        <h6 class="text-muted mb-1">{{ __('Resistencia a la insulina') }}</h6>
                                        @forelse ($labResult->insulinResistanceIndicators() as $name => $indicator)
                                            <p class="mb-1">
                                                <strong>{{ $name }}</strong> {{ number_format($indicator['value'], 2, ',', '.') }}
                                                @if($indicator['high'])
                                                    <span class="badge badge-danger">{{ __('Sugiere resistencia a la insulina') }}</span>
                                                @else
                                                    <span class="badge badge-success">{{ __('Dentro de rango') }}</span>
                                                @endif
                                            </p>
                                        @empty
                                            <p class="text-muted mb-0">{{ __('Requiere glicemia con insulina, o triglicéridos con glicemia o HDL') }}</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($record->notes)
                            <h6 class="heading-small text-muted mt-4">{{ __('Observaciones') }}</h6>
                            <p style="white-space: pre-line">{{ $record->notes }}</p>
                        @endif

                        <hr>
                        <p class="small text-muted mb-0">
                            {{ __('Registrada por :name el :date', ['name' => $record->author?->name ?? '—', 'date' => $record->created_at->format('d/m/Y H:i')]) }}
                            @if($record->updated_at->ne($record->created_at))
                                · {{ __('Última modificación por :name el :date', ['name' => $record->editor?->name ?? '—', 'date' => $record->updated_at->format('d/m/Y H:i')]) }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
