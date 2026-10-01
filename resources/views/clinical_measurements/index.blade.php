@extends('layouts.app', [
    'namePage' => 'Mediciones clínicas',
    'activePage' => 'patient',
])

@php
    $value = fn ($v, $unit = '') => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',').($unit ? ' '.$unit : '');
    // Variation against the previous measurement; null when either value is missing.
    $delta = fn (string $field) => $latest?->{$field} !== null && $previous?->{$field} !== null
        ? round($latest->{$field} - $previous->{$field}, 1) : null;
    $deltaBadge = function (?float $d, string $unit) {
        if ($d === null) {
            return '';
        }
        // Neutral colour: whether a change is good depends on the patient (e.g. underweight).
        $text = ($d > 0 ? '+' : '').number_format($d, 1, ',', '.').' '.$unit;

        return '<span class="text-sm font-weight-bolder text-secondary">'.e($text).'</span>';
    };
    $bmiClass = fn (?string $c) => match ($c) { 'Normal' => 'bg-gradient-success', 'Sobrepeso' => 'bg-gradient-warning', 'Obesidad' => 'bg-gradient-danger', default => 'bg-gradient-info' };
    $bpClass = fn (?string $c) => match ($c) { 'Normal' => 'bg-gradient-success', 'Elevada' => 'bg-gradient-warning', default => 'bg-gradient-danger' };
@endphp

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <div>
              <h6 class="mb-0">{{ __('Registro de mediciones clínicas') }}</h6>
              <p class="text-sm text-secondary mb-0">
                {{ $patient->fullName() }} · {{ __('RUT') }} {{ $patient->rut }}
                @if($patient->age !== null)
                  · {{ $patient->age }} {{ __('años') }}
                @endif
                · {{ trans_choice(':count medición|:count mediciones', $measurements->total(), ['count' => $measurements->total()]) }}
              </p>
            </div>
            <div class="ms-auto d-flex flex-wrap gap-2">
              @can('updateClinicalRecord', $patient)
                <a href="{{ route('patient.measurements.create', $patient) }}" class="btn btn-primary btn-sm mb-0">
                  <i class="fas fa-plus me-1"></i>{{ __('Registrar medición') }}
                </a>
              @endcan
              <a href="{{ route('patient.lab-results.index', $patient) }}" class="btn btn-outline-danger btn-sm mb-0">{{ __('Exámenes') }}</a>
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
              {{ __('Última medición: :date', ['date' => $latest->measured_at->format('d/m/Y')]) }}
              @if($previous)
                · {{ __('variación respecto al :date', ['date' => $previous->measured_at->format('d/m/Y')]) }}
              @endif
            </p>
            <div class="row g-3">
              <div class="col-sm-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                  <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ __('Peso') }}</p>
                  <h5 class="mb-0">{{ $value($latest->weight_kg, 'kg') }} {!! $deltaBadge($delta('weight_kg'), 'kg') !!}</h5>
                  @if($latest->body_fat_pct !== null)
                    <p class="text-xs text-secondary mb-0">{{ __('Grasa corporal') }}: {{ $value($latest->body_fat_pct, '%') }}</p>
                  @endif
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                  <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ __('IMC') }}</p>
                  @if($latest->bmi() !== null)
                    <h5 class="mb-1">{{ number_format($latest->bmi(), 1, ',', '.') }} <small class="text-sm text-secondary">kg/m²</small></h5>
                    <span class="badge badge-sm {{ $bmiClass($latest->bmiCategory()) }}">{{ $latest->bmiCategory() }}</span>
                  @else
                    <p class="text-sm text-secondary mb-0">{{ __('Requiere peso y talla') }}</p>
                  @endif
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                  <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ __('Cintura') }}</p>
                  <h5 class="mb-1">{{ $value($latest->waist_cm, 'cm') }} {!! $deltaBadge($delta('waist_cm'), 'cm') !!}</h5>
                  @if($latest->waistToHeight() !== null)
                    <p class="text-xs mb-0">
                      {{ __('Cintura/talla') }}: {{ number_format($latest->waistToHeight(), 2, ',', '.') }}
                      @if($latest->waistToHeight() > \App\Models\ClinicalMeasurement::WAIST_TO_HEIGHT_THRESHOLD)
                        <span class="badge badge-sm bg-gradient-danger">{{ __('Riesgo cardiometabólico') }}</span>
                      @endif
                    </p>
                  @endif
                  @if($latest->waistToHip() !== null)
                    <p class="text-xs text-secondary mb-0">{{ __('Cintura/cadera') }}: {{ number_format($latest->waistToHip(), 2, ',', '.') }}</p>
                  @endif
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                  <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ __('Presión arterial') }}</p>
                  @if($latest->bloodPressure())
                    <h5 class="mb-1">{{ $latest->bloodPressure() }} <small class="text-sm text-secondary">mmHg</small></h5>
                    <span class="badge badge-sm {{ $bpClass($latest->bloodPressureCategory()) }}">{{ $latest->bloodPressureCategory() }}</span>
                  @else
                    <h5 class="mb-1">—</h5>
                  @endif
                  @if($latest->capillary_glucose !== null)
                    <p class="text-xs text-secondary mb-0">{{ __('Glicemia capilar') }}: {{ $value($latest->capillary_glucose, 'mg/dL') }}</p>
                  @endif
                </div>
              </div>
            </div>
            <p class="text-xxs text-secondary mt-2 mb-0">
              {{ __('Valores referenciales: IMC según OMS, cintura/talla > 0,5 y presión arterial según ACC/AHA 2017.') }}
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
            <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="{{ __('Indicador') }}" data-chart-metrics>
              <button type="button" class="btn btn-outline-primary mb-0 active" data-metric="weight_kg">{{ __('Peso') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="bmi">{{ __('IMC') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="waist_cm">{{ __('Cintura') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="blood_pressure">{{ __('Presión') }}</button>
              <button type="button" class="btn btn-outline-primary mb-0" data-metric="capillary_glucose">{{ __('Glicemia') }}</button>
            </div>
          </div>
          <div class="card-body p-3">
            <div class="chart">
              <canvas id="evolution-chart" class="chart-canvas" height="280" data-series="{{ json_encode($series) }}" data-metrics="{{ json_encode([
                'weight_kg' => [['key' => 'weight_kg', 'label' => __('Peso (kg)')]],
                'bmi' => [['key' => 'bmi', 'label' => __('IMC (kg/m²)')]],
                'waist_cm' => [['key' => 'waist_cm', 'label' => __('Cintura (cm)')]],
                'blood_pressure' => [
                    ['key' => 'systolic_bp', 'label' => __('Sistólica (mmHg)')],
                    ['key' => 'diastolic_bp', 'label' => __('Diastólica (mmHg)')],
                ],
                'capillary_glucose' => [['key' => 'capillary_glucose', 'label' => __('Glicemia capilar (mg/dL)')]],
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
        </div>
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">{{ __('Fecha') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('Peso') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('Talla') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('IMC') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('Cintura') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('Cadera') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('% Grasa') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('P. arterial') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('FC') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('Glicemia') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('Registrado por') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-4">{{ __('Acciones') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($measurements as $m)
                  <tr>
                    <td class="ps-3">
                      <span class="text-sm">{{ $m->measured_at->format('d/m/Y') }}</span>
                      @if($m->notes)
                        <i class="fas fa-comment-medical text-secondary ms-1" title="{{ $m->notes }}" aria-label="{{ __('Observaciones') }}: {{ $m->notes }}"></i>
                      @endif
                      @if($m->diagnosis_id)
                        <a href="{{ route('result', $m->diagnosis_id) }}" class="ms-1" title="{{ __('Ver consulta asociada') }}" aria-label="{{ __('Ver consulta asociada') }}"><i class="fas fa-stethoscope"></i></a>
                      @endif
                    </td>
                    <td class="text-sm">{{ $value($m->weight_kg, 'kg') }}</td>
                    <td class="text-sm">{{ $value($m->height_cm, 'cm') }}</td>
                    <td class="text-sm">
                      @if($m->bmi() !== null)
                        {{ number_format($m->bmi(), 1, ',', '.') }}
                        <span class="text-xs text-secondary d-block">{{ $m->bmiCategory() }}</span>
                      @else
                        —
                      @endif
                    </td>
                    <td class="text-sm">{{ $value($m->waist_cm, 'cm') }}</td>
                    <td class="text-sm">{{ $value($m->hip_cm, 'cm') }}</td>
                    <td class="text-sm">{{ $value($m->body_fat_pct, '%') }}</td>
                    <td class="text-sm">{{ $m->bloodPressure() ?? '—' }}</td>
                    <td class="text-sm">{{ $m->heart_rate ?? '—' }}</td>
                    <td class="text-sm">{{ $value($m->capillary_glucose, 'mg/dL') }}</td>
                    <td><span class="text-sm text-secondary">{{ $m->author?->name ?? '—' }}</span></td>
                    <td class="text-end pe-4 text-nowrap">
                      @can('update', $m)
                        <a href="{{ route('patient.measurements.edit', [$patient, $m]) }}" class="btn btn-sm btn-icon-only btn-outline-success mb-0" title="{{ __('Editar') }}" aria-label="{{ __('Editar medición del :date', ['date' => $m->measured_at->format('d/m/Y')]) }}">
                          <i class="fas fa-pen"></i>
                        </a>
                      @endcan
                      @can('delete', $m)
                        <form action="{{ route('patient.measurements.destroy', [$patient, $m]) }}" method="post" class="d-inline"
                          onsubmit="return confirm('{{ __('¿Eliminar la medición del :date?', ['date' => $m->measured_at->format('d/m/Y')]) }}')">
                          @csrf
                          @method('delete')
                          <button type="submit" class="btn btn-sm btn-icon-only btn-outline-danger mb-0" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar medición del :date', ['date' => $m->measured_at->format('d/m/Y')]) }}">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      @endcan
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="12" class="text-center text-sm text-secondary py-4">
                      {{ __('Aún no hay mediciones registradas para este paciente.') }}
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="px-3 pt-3">
            {{ $measurements->links() }}
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
