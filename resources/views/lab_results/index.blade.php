@extends('layouts.app', [
    'namePage' => 'Exámenes de laboratorio',
    'activePage' => 'patient',
])

@use('App\Models\LabResult')

@php
    $number = fn (?float $v, int $decimals = 2) => $v === null ? '—' : number_format($v, $decimals, ',', '.');
    $reference = function (array $analyte) {
        return isset($analyte['min']) ? '≥ '.$analyte['min'] : '≤ '.str_replace('.', ',', (string) $analyte['max']);
    };
    $th = 'text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2';
@endphp

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <div>
              <h6 class="mb-0">{{ __('Exámenes de laboratorio') }}</h6>
              <p class="text-sm text-secondary mb-0">
                {{ $patient->fullName() }} · {{ __('RUT') }} {{ $patient->rut }}
                @if($patient->age !== null)
                  · {{ $patient->age }} {{ __('años') }}
                @endif
                · {{ trans_choice(':count examen|:count exámenes', $labResults->total(), ['count' => $labResults->total()]) }}
              </p>
            </div>
            <div class="ms-auto d-flex flex-wrap gap-2">
              @can('updateClinicalRecord', $patient)
                <a href="{{ route('patient.lab-results.create', $patient) }}" class="btn btn-primary btn-sm mb-0">
                  <i class="fas fa-plus me-1"></i>{{ __('Registrar examen') }}
                </a>
              @endcan
              <a href="{{ route('patient.measurements.index', $patient) }}" class="btn btn-outline-info btn-sm mb-0">{{ __('Mediciones') }}</a>
              <a href="{{ route('patient.clinical-record.show', $patient) }}" class="btn btn-outline-warning btn-sm mb-0">{{ __('Ficha clínica') }}</a>
              <a href="{{ route('patient.index') }}" class="btn btn-outline-primary btn-sm mb-0">{{ __('Volver') }}</a>
            </div>
          </div>
          <div class="mt-3">
            @include('alerts.success')
            @include('alerts.errors')
          </div>
        </div>

        @if($latest)
          <div class="card-body pt-0">
            <p class="text-xs text-secondary mb-2">
              {{ __('Último examen: :date', ['date' => $latest->taken_at->format('d/m/Y')]) }}
              @if($latest->diagnosis_id)
                · <a href="{{ route('result', $latest->diagnosis_id) }}">{{ __('ver consulta') }}</a>
              @endif
            </p>
            <div class="row g-3">
              <div class="col-lg-7">
                <div class="table-responsive border rounded-3">
                  <table class="table table-sm align-items-center mb-0">
                    <thead>
                      <tr>
                        <th class="{{ $th }} ps-3">{{ __('Examen') }}</th>
                        <th class="{{ $th }}">{{ __('Resultado') }}</th>
                        <th class="{{ $th }}">{{ __('Referencia') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach (LabResult::ANALYTES as $field => $analyte)
                        @continue($latest->{$field} === null)
                        <tr>
                          <td class="text-sm ps-3">{{ __($analyte['label']) }}</td>
                          <td class="text-sm">
                            @include('lab_results._value', ['result' => $latest, 'field' => $field]) {{ $analyte['unit'] }}
                            @if($latest->isOutOfRange($field))
                              <span class="badge badge-sm bg-gradient-danger ms-1">{{ __('Fuera de rango') }}</span>
                            @endif
                          </td>
                          <td class="text-xs text-secondary">{{ $reference($analyte) }} {{ $analyte['unit'] }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="border rounded-3 p-3 h-100">
                  <p class="text-xs text-uppercase text-secondary font-weight-bold mb-2">{{ __('Indicadores de resistencia a la insulina') }}</p>
                  @forelse ($latest->insulinResistanceIndicators() as $name => $indicator)
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <div>
                        <span class="text-sm font-weight-bold">{{ $name }}</span>
                        <span class="text-xs text-secondary d-block">{{ __('Referencial: > :t', ['t' => $number($indicator['threshold'], 1)]) }}</span>
                      </div>
                      <div class="text-end">
                        <h5 class="mb-0">{{ $number($indicator['value']) }}</h5>
                        @if($indicator['high'])
                          <span class="badge badge-sm bg-gradient-danger">{{ __('Sugiere resistencia a la insulina') }}</span>
                        @else
                          <span class="badge badge-sm bg-gradient-success">{{ __('Dentro de rango') }}</span>
                        @endif
                      </div>
                    </div>
                  @empty
                    <p class="text-sm text-secondary mb-0">
                      {{ __('Requiere glicemia con insulina (HOMA-IR), triglicéridos con glicemia (TyG) o triglicéridos con HDL.') }}
                    </p>
                  @endforelse
                </div>
              </div>
            </div>
            <p class="text-xxs text-secondary mt-2 mb-0">
              {{ __('Rangos de referencia para adultos, solo orientativos; considere los del laboratorio que emitió el informe.') }}
            </p>
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(count($series) >= 2)
    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-header pb-0 pt-3 bg-transparent d-flex flex-wrap align-items-center gap-2">
            <h6 class="mb-0">{{ __('Evolución') }}</h6>
            <div class="btn-group btn-group-sm ms-auto flex-wrap" role="group" aria-label="{{ __('Indicador') }}" data-chart-metrics>
              <button type="button" class="btn btn-outline-primary mb-0 active" data-metric="fasting_glucose">{{ __('Glicemia') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="fasting_insulin">{{ __('Insulina') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="hba1c">{{ __('HbA1c') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="homa_ir">{{ __('HOMA-IR') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="tyg">{{ __('TyG') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="lipids">{{ __('Lípidos') }}</button>
            </div>
          </div>
          <div class="card-body p-3">
            <div class="chart">
              <canvas id="evolution-chart" class="chart-canvas" height="280" data-series="{{ json_encode($series) }}" data-metrics="{{ json_encode([
                'fasting_glucose' => [['key' => 'fasting_glucose', 'label' => __('Glicemia en ayunas (mg/dL)')]],
                'fasting_insulin' => [['key' => 'fasting_insulin', 'label' => __('Insulina basal (µU/mL)')]],
                'hba1c' => [['key' => 'hba1c', 'label' => __('HbA1c (%)')]],
                'homa_ir' => [['key' => 'homa_ir', 'label' => 'HOMA-IR']],
                'tyg' => [['key' => 'tyg', 'label' => __('Índice TyG')]],
                'lipids' => [
                    ['key' => 'triglycerides', 'label' => __('Triglicéridos (mg/dL)')],
                    ['key' => 'ldl', 'label' => __('LDL (mg/dL)')],
                    ['key' => 'hdl', 'label' => __('HDL (mg/dL)')],
                ],
              ]) }}"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0">
          <h6 class="mb-0">{{ __('Historial') }}</h6>
          <p class="text-xs text-secondary mb-0">{{ __('Glicemia, colesterol y triglicéridos en mg/dL; insulina en µU/mL. En rojo, fuera del rango de referencia.') }}</p>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="{{ $th }} ps-3">{{ __('Fecha') }}</th>
                  <th class="{{ $th }}">{{ __('Glicemia') }}</th>
                  <th class="{{ $th }}">{{ __('Insulina') }}</th>
                  <th class="{{ $th }}">{{ __('HbA1c %') }}</th>
                  <th class="{{ $th }}">{{ __('HOMA-IR') }}</th>
                  <th class="{{ $th }}">{{ __('Col. total') }}</th>
                  <th class="{{ $th }}">{{ __('HDL') }}</th>
                  <th class="{{ $th }}">{{ __('LDL') }}</th>
                  <th class="{{ $th }}">{{ __('Triglicéridos') }}</th>
                  <th class="{{ $th }}">{{ __('TyG') }}</th>
                  <th class="{{ $th }}">{{ __('Consulta') }}</th>
                  <th class="{{ $th }}">{{ __('Registrado por') }}</th>
                  <th class="{{ $th }} text-end pe-4">{{ __('Acciones') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($labResults as $r)
                  <tr>
                    <td class="ps-3">
                      <span class="text-sm">{{ $r->taken_at->format('d/m/Y') }}</span>
                      @if($r->notes)
                        <i class="fas fa-comment-medical text-secondary ms-1" title="{{ $r->notes }}" aria-label="{{ __('Observaciones') }}: {{ $r->notes }}"></i>
                      @endif
                    </td>
                    @foreach (['fasting_glucose', 'fasting_insulin', 'hba1c'] as $field)
                      <td class="text-sm">@include('lab_results._value', ['result' => $r, 'field' => $field])</td>
                    @endforeach
                    <td class="text-sm">
                      <span @class(['text-danger font-weight-bold' => $r->homaIr() > config('clinical.insulin_resistance.homa_ir')])>{{ $number($r->homaIr()) }}</span>
                    </td>
                    @foreach (['total_cholesterol', 'hdl', 'ldl', 'triglycerides'] as $field)
                      <td class="text-sm">@include('lab_results._value', ['result' => $r, 'field' => $field])</td>
                    @endforeach
                    <td class="text-sm">
                      <span @class(['text-danger font-weight-bold' => $r->tygIndex() > config('clinical.insulin_resistance.tyg')])>{{ $number($r->tygIndex()) }}</span>
                    </td>
                    <td class="text-sm">
                      @if($r->diagnosis)
                        <a href="{{ route('result', $r->diagnosis) }}">{{ $r->diagnosis->created_at->format('d/m/Y') }}</a>
                      @else
                        —
                      @endif
                    </td>
                    <td><span class="text-sm text-secondary">{{ $r->author?->name ?? '—' }}</span></td>
                    <td class="text-end pe-4 text-nowrap">
                      @can('update', $r)
                        <a href="{{ route('patient.lab-results.edit', [$patient, $r]) }}" class="btn btn-sm btn-icon-only btn-outline-success mb-0" title="{{ __('Editar') }}" aria-label="{{ __('Editar examen del :date', ['date' => $r->taken_at->format('d/m/Y')]) }}">
                          <i class="fas fa-pen"></i>
                        </a>
                      @endcan
                      @can('delete', $r)
                        <form action="{{ route('patient.lab-results.destroy', [$patient, $r]) }}" method="post" class="d-inline"
                          onsubmit="return confirm('{{ __('¿Eliminar el examen del :date?', ['date' => $r->taken_at->format('d/m/Y')]) }}')">
                          @csrf
                          @method('delete')
                          <button type="submit" class="btn btn-sm btn-icon-only btn-outline-danger mb-0" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar examen del :date', ['date' => $r->taken_at->format('d/m/Y')]) }}">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      @endcan
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="13" class="text-center text-sm text-secondary py-4">
                      {{ __('Aún no hay exámenes registrados para este paciente.') }}
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="px-3 pt-3">
            {{ $labResults->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@if(count($series) >= 2)
  @push('js')
    @vite('resources/js/evolution-chart.js')
  @endpush
@endif
