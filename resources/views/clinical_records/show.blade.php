@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Ficha clínica',
    'activePage' => 'patient',
    'activeNav' => '',
])

@php
    $value = fn ($v, $unit = '') => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',').($unit ? ' '.$unit : '');
    $homa = $record->homaIr();
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
                                    @if($patient->birthdate)
                                        · {{ \Carbon\Carbon::parse($patient->birthdate)->age }} {{ __('años') }}
                                    @endif
                                    · {{ $patient->gender === 'H' ? __('Hombre') : __('Mujer') }}
                                </p>
                            </div>
                            <div class="col-md-5 text-right">
                                @can('updateClinicalRecord', $patient)
                                    <a href="{{ route('patient.clinical-record.edit', $patient) }}" class="btn btn-info btn-round">{{ __('Editar ficha') }}</a>
                                @endcan
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
                                    <dt>{{ __('Circunferencia de cintura') }}</dt><dd>{{ $value($record->waist_cm, 'cm') }}</dd>
                                </dl>
                            </div>
                        </div>

                        <h6 class="heading-small text-muted mt-4">
                            {{ __('Exámenes de laboratorio') }}
                            @if($record->lab_date)
                                <small>({{ $record->lab_date->format('d/m/Y') }})</small>
                            @endif
                        </h6>
                        <div class="row">
                            <div class="col-md-8">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr><td>{{ __('Glicemia en ayunas') }}</td><td>{{ $value($record->fasting_glucose, 'mg/dL') }}</td></tr>
                                        <tr><td>{{ __('Insulina basal') }}</td><td>{{ $value($record->fasting_insulin, 'µU/mL') }}</td></tr>
                                        <tr><td>{{ __('HbA1c') }}</td><td>{{ $value($record->hba1c, '%') }}</td></tr>
                                        <tr><td>{{ __('Colesterol total') }}</td><td>{{ $value($record->total_cholesterol, 'mg/dL') }}</td></tr>
                                        <tr><td>{{ __('HDL') }}</td><td>{{ $value($record->hdl, 'mg/dL') }}</td></tr>
                                        <tr><td>{{ __('LDL') }}</td><td>{{ $value($record->ldl, 'mg/dL') }}</td></tr>
                                        <tr><td>{{ __('Triglicéridos') }}</td><td>{{ $value($record->triglycerides, 'mg/dL') }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-stats text-center p-3">
                                    <h6 class="text-muted mb-1">HOMA-IR</h6>
                                    @if($homa === null)
                                        <p class="text-muted mb-0">{{ __('Requiere glicemia e insulina basal') }}</p>
                                    @else
                                        <h2 class="mb-1">{{ number_format($homa, 2, ',', '.') }}</h2>
                                        @if($record->suggestsInsulinResistance())
                                            <span class="badge badge-danger">{{ __('Sugiere resistencia a la insulina') }}</span>
                                        @else
                                            <span class="badge badge-success">{{ __('Dentro de rango') }}</span>
                                        @endif
                                        <p class="small text-muted mt-2 mb-0">
                                            {{ __('Referencial: > :t sugiere resistencia a la insulina.', ['t' => number_format(\App\Models\ClinicalRecord::HOMA_IR_THRESHOLD, 1, ',', '.')]) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

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
