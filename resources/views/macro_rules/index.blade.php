@extends('layouts.app', [
    'namePage' => 'Reglas de macronutrientes',
    'class' => 'sidebar-mini',
    'activePage' => 'macro-rules',
    'activeNav' => '',
])

@section('content')
  <div class="panel-header">
  </div>
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            @can('create', \App\Models\MacroRule::class)
              <a class="btn btn-primary btn-round text-white pull-right" href="{{ route('macro-rules.create') }}">{{ __('Agregar regla') }}</a>
            @endcan
            <h4 class="card-title">{{ __('Reglas de macronutrientes') }}</h4>
            <p class="text-sm text-secondary mb-0">
              {{ __('Se aplican sobre las reglas propias del sistema experto (MAC-01 a MAC-09) al sugerir la distribución de macronutrientes. Si varias reglas fijan el mismo límite, gana el más restrictivo.') }}
            </p>
            <div class="col-12 mt-2">
              @include('alerts.success')
              @include('alerts.errors')
            </div>
          </div>
          <div class="card-body">
            @if ($rules->isEmpty())
              <p class="text-sm text-secondary">{{ __('No hay reglas configuradas.') }}</p>
            @else
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>{{ __('Código') }}</th>
                      <th>{{ __('Nombre') }}</th>
                      <th>{{ __('Si') }}</th>
                      <th>{{ __('Entonces') }}</th>
                      <th>{{ __('Estado') }}</th>
                      <th class="text-right">{{ __('Acciones') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($rules as $rule)
                      <tr>
                        <td class="text-xs">{{ $rule->code() }}</td>
                        <td class="text-wrap" style="min-width: 180px;">
                          {{ $rule->name }}
                          @if ($rule->advice)
                            <p class="text-xs text-secondary mb-0">{{ $rule->advice }}</p>
                          @endif
                        </td>
                        <td>{{ $rule->condition() }}</td>
                        <td class="text-sm text-wrap" style="min-width: 220px;">{{ $rule->effects() }}</td>
                        <td>
                          <span class="badge bg-gradient-{{ $rule->active ? 'success' : 'secondary' }}">{{ $rule->active ? __('Activa') : __('Inactiva') }}</span>
                        </td>
                        <td class="text-right text-nowrap">
                          @can('update', $rule)
                            <a href="{{ route('macro-rules.edit', $rule) }}" class="btn btn-success btn-sm mb-0" title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}">
                              <i class="fas fa-pen" aria-hidden="true"></i>
                            </a>
                          @endcan
                          @can('delete', $rule)
                            <form action="{{ route('macro-rules.destroy', $rule) }}" method="post" style="display:inline-block;">
                              @csrf
                              @method('delete')
                              <button type="button" class="btn btn-danger btn-sm mb-0" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar') }}"
                                onclick="confirm('{{ __('¿Está seguro de que desea eliminar esta regla?') }}') ? this.parentElement.submit() : ''">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                              </button>
                            </form>
                          @endcan
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
