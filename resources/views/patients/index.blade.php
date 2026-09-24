@extends('layouts.app', [
    'namePage' => 'Pacientes',
    'activePage' => 'patient',
])

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <div>
              <h6 class="mb-0">{{ __('Pacientes') }}</h6>
              <p class="text-sm text-secondary mb-0">{{ trans_choice(':count paciente|:count pacientes', $patients->total(), ['count' => $patients->total()]) }}</p>
            </div>
            <form method="get" action="{{ route('patient.index') }}" class="ms-auto d-flex gap-2" role="search">
              <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span>
                <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="{{ __('Nombre, RUT o correo') }}" aria-label="{{ __('Buscar pacientes') }}">
              </div>
              @if($search !== '')
                <a href="{{ route('patient.index') }}" class="btn btn-sm btn-outline-secondary mb-0">{{ __('Limpiar') }}</a>
              @endif
            </form>
            @can('create', \App\Models\Patient::class)
              <a class="btn btn-primary btn-sm mb-0" href="{{ route('patient.create') }}">
                <i class="fas fa-plus me-1"></i>{{ __('Agregar paciente') }}
              </a>
            @endcan
          </div>
          <div class="mt-3">
            @include('alerts.success')
            @include('alerts.errors')
          </div>
        </div>

        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">{{ __('Paciente') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('RUT') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('Nacimiento') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">{{ __('Registrado por') }}</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-4">{{ __('Acciones') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($patients as $patient)
                  <tr>
                    <td>
                      <div class="d-flex px-3 py-1">
                        <img src="{{ asset('assets/img/default-avatar.png') }}" class="avatar avatar-sm me-3" alt="">
                        <div class="d-flex flex-column justify-content-center">
                          <h6 class="mb-0 text-sm">{{ $patient->fullName() }}</h6>
                          <p class="text-xs text-secondary mb-0">
                            {{ $patient->genderLabel() }}@if($patient->email) · {{ $patient->email }}@endif
                          </p>
                        </div>
                      </div>
                    </td>
                    <td><span class="text-sm">{{ $patient->rut }}</span></td>
                    <td>
                      <p class="text-sm mb-0">{{ $patient->birthdate?->format('d-m-Y') }}</p>
                      @if($patient->age !== null)
                        <p class="text-xs text-secondary mb-0">{{ $patient->age }} {{ __('años') }}</p>
                      @endif
                    </td>
                    <td><span class="text-sm text-secondary">{{ $patient->user->name ?? '—' }}</span></td>
                    <td class="text-end pe-4 text-nowrap">
                      @can('create', \App\Models\Diagnosis::class)
                        <a href="{{ route('diagnosis.new', $patient->id) }}" class="btn btn-sm btn-primary mb-0 me-1">{{ __('Consultar') }}</a>
                      @endcan
                      @can('viewClinicalRecord', $patient)
                        <a href="{{ route('patient.clinical-record.show', $patient) }}" class="btn btn-sm btn-icon-only btn-outline-warning mb-0" title="{{ __('Ficha clínica') }}" aria-label="{{ __('Ficha clínica de :name', ['name' => $patient->fullName()]) }}">
                          <i class="fas fa-notes-medical"></i>
                        </a>
                      @endcan
                      <a href="{{ route('diagnosis.all', $patient->id) }}" class="btn btn-sm btn-icon-only btn-outline-info mb-0" title="{{ __('Historial de consultas') }}" aria-label="{{ __('Historial de :name', ['name' => $patient->fullName()]) }}">
                        <i class="fas fa-history"></i>
                      </a>
                      @can('update', $patient)
                        <a href="{{ route('patient.edit', $patient) }}" class="btn btn-sm btn-icon-only btn-outline-success mb-0" title="{{ __('Editar') }}" aria-label="{{ __('Editar a :name', ['name' => $patient->fullName()]) }}">
                          <i class="fas fa-pen"></i>
                        </a>
                      @endcan
                      @can('delete', $patient)
                        <form action="{{ route('patient.destroy', $patient) }}" method="post" class="d-inline"
                          onsubmit="return confirm('{{ __('¿Eliminar a :name y todo su historial? Esta acción no se puede deshacer.', ['name' => addslashes($patient->fullName())]) }}')">
                          @csrf
                          @method('delete')
                          <button type="submit" class="btn btn-sm btn-icon-only btn-outline-danger mb-0" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar a :name', ['name' => $patient->fullName()]) }}">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      @endcan
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-sm text-secondary py-4">
                      {{ $search !== '' ? __('No hay pacientes que coincidan con ":q".', ['q' => $search]) : __('Aún no hay pacientes registrados.') }}
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="px-3 pt-3">
            {{ $patients->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
