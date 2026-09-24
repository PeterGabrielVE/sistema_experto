@extends('layouts.app', [
    'namePage' => 'Inicio',
    'activePage' => 'home',
])

@php
    $cards = [
        ['title' => 'Pacientes', 'value' => $stats['patients'], 'hint' => '+'.$stats['patients_month'].' este mes', 'icon' => 'ni ni-single-02', 'color' => 'primary'],
        ['title' => 'Consultas del mes', 'value' => $stats['diagnoses_month'], 'hint' => now()->translatedFormat('F Y'), 'icon' => 'ni ni-chart-bar-32', 'color' => 'success'],
        ['title' => 'Clasificadas por ML', 'value' => $stats['ml_share'] === null ? '—' : $stats['ml_share'].'%', 'hint' => 'del total de consultas', 'icon' => 'ni ni-bulb-61', 'color' => 'warning'],
        ['title' => 'Equipo clínico', 'value' => $stats['doctors'], 'hint' => 'doctores y doctores jefe', 'icon' => 'ni ni-badge', 'color' => 'info'],
    ];
@endphp

@section('content')
  <div class="row">
    @foreach ($cards as $card)
      <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card">
          <div class="card-body p-3">
            <div class="row">
              <div class="col-8">
                <div class="numbers">
                  <p class="text-sm mb-0 text-uppercase font-weight-bold">{{ $card['title'] }}</p>
                  <h5 class="font-weight-bolder mb-0">{{ $card['value'] }}</h5>
                  <p class="mb-0 text-sm text-secondary">{{ $card['hint'] }}</p>
                </div>
              </div>
              <div class="col-4 text-end">
                <div class="icon icon-shape bg-{{ $card['color'] }} shadow-{{ $card['color'] }} text-center rounded-circle">
                  <i class="{{ $card['icon'] }} text-lg opacity-10 text-white" aria-hidden="true"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="row">
    <div class="col-lg-8 mb-4">
      <div class="card z-index-2 h-100">
        <div class="card-header pb-0 pt-3 bg-transparent">
          <h6 class="text-capitalize mb-0">{{ __('Consultas por mes') }}</h6>
          <p class="text-sm mb-0 text-secondary">{{ now()->year }}</p>
        </div>
        <div class="card-body p-3">
          <div class="chart">
            <canvas id="chart-diagnoses" class="chart-canvas" height="300"
              data-url="{{ route('diagnoses/chart') }}" data-label="{{ __('Consultas') }}"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4 mb-4">
      <div class="card z-index-2 h-100">
        <div class="card-header pb-0 pt-3 bg-transparent">
          <h6 class="text-capitalize mb-0">{{ __('Pacientes ingresados') }}</h6>
          <p class="text-sm mb-0 text-secondary">{{ now()->year }}</p>
        </div>
        <div class="card-body p-3">
          <div class="chart">
            <canvas id="chart-patients" class="chart-canvas" height="300"
              data-url="{{ route('patients/chart') }}" data-label="{{ __('Pacientes') }}"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-7 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0 p-3">
          <h6 class="mb-0">{{ __('Recomendaciones') }}</h6>
          <p class="text-sm mb-0 text-secondary">{{ __('Base de conocimiento del sistema experto') }}</p>
        </div>
        <div class="card-body p-3">
          <ul class="list-group">
            @forelse($recomm as $re)
              <li class="list-group-item border-0 d-flex align-items-center px-0 mb-1">
                <div class="icon icon-shape icon-sm me-3 bg-light text-center rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ni ni-bulb-61 text-warning text-sm"></i>
                </div>
                <span class="text-sm text-dark">{{ $re->description }}</span>
              </li>
            @empty
              <li class="list-group-item border-0 px-0 text-sm text-secondary">{{ __('Aún no hay recomendaciones.') }}</li>
            @endforelse
          </ul>
          {{ $recomm->links() }}
        </div>
      </div>
    </div>
    <div class="col-lg-5 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0 p-3">
          <h6 class="mb-0">{{ __('Equipo clínico') }}</h6>
        </div>
        <div class="card-body p-3">
          <ul class="list-group">
            @forelse($users as $u)
              <li class="list-group-item border-0 d-flex justify-content-between align-items-center px-0 mb-1">
                <div class="d-flex flex-column">
                  <h6 class="mb-0 text-sm">{{ $u->name }}</h6>
                  <span class="text-xs text-secondary">{{ $u->email }}</span>
                </div>
                <span class="badge bg-light text-dark">{{ $u->role()?->label() }}</span>
              </li>
            @empty
              <li class="list-group-item border-0 px-0 text-sm text-secondary">{{ __('Sin doctores registrados.') }}</li>
            @endforelse
          </ul>
          {{ $users->links() }}
        </div>
      </div>
    </div>
  </div>
@endsection

@push('js')
  @vite('resources/js/dashboard.js')
@endpush
