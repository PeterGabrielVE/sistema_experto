@extends('layouts.app', [
    'namePage' => 'Alimentos',
    'class' => 'sidebar-mini',
    'activePage' => 'foods',
    'activeNav' => '',
])

@php
    // Legacy columns are text, some with a decimal comma.
    $number = fn ($value) => $value === null || $value === ''
        ? '—'
        : rtrim(rtrim(number_format((float) str_replace(',', '.', $value), 1, ',', '.'), '0'), ',');
@endphp

@section('content')
  <div class="panel-header">
  </div>
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            @can('create', \App\Models\Food::class)
              <a class="btn btn-primary btn-round text-white pull-right" href="{{ route('foods.create') }}">{{ __('Agregar alimento') }}</a>
            @endcan
            @can('export', \App\Models\Food::class)
              <a class="btn btn-outline-primary btn-round pull-right me-2" href="{{ route('foods.export') }}">{{ __('Descargar CSV') }}</a>
            @endcan
            <h4 class="card-title">{{ __('Alimentos') }}</h4>
            <p class="text-sm text-secondary mb-0">
              {{ __('Catálogo de porciones de intercambio que usa el generador de menús. Los datos a revisar no impiden usar el alimento.') }}
            </p>
            <div class="col-12 mt-2">
              @include('alerts.success')
              @include('alerts.errors')
              @if (session('warning'))
                <div class="alert alert-warning text-white" role="alert">{{ session('warning') }}</div>
              @endif
            </div>

            <form method="get" action="{{ route('foods.index') }}" class="row g-2 align-items-end mt-2" role="search">
              <div class="col-md-5">
                <label class="form-control-label" for="food-search">{{ __('Buscar por nombre') }}</label>
                <input type="search" name="q" id="food-search" class="form-control" value="{{ $search }}" maxlength="100">
              </div>
              <div class="col-md-4">
                <label class="form-control-label" for="food-group">{{ __('Grupo') }}</label>
                <select name="grupo" id="food-group" class="form-control">
                  <option value="">{{ __('Todos los grupos') }}</option>
                  @foreach ($groups as $option)
                    <option value="{{ $option }}" @selected($option === $group)>{{ $option }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <button type="submit" class="btn btn-primary mb-0">{{ __('Filtrar') }}</button>
                @if ($search !== '' || $group)
                  <a href="{{ route('foods.index') }}" class="btn btn-outline-secondary mb-0">{{ __('Limpiar') }}</a>
                @endif
              </div>
            </form>
          </div>
          <div class="card-body">
            <p class="text-sm mb-2">
              {{ trans_choice(':count alimento|:count alimentos', $foods->count()) }}
              @if ($flags->isNotEmpty())
                · <span class="text-warning">{{ __(':count con datos a revisar', ['count' => $flags->count()]) }}</span>
              @endif
            </p>

            @if ($foods->isEmpty())
              <p class="text-sm text-secondary">
                {{ $search !== '' || $group ? __('Ningún alimento coincide con la búsqueda.') : __('El catálogo no tiene alimentos.') }}
              </p>
            @else
              <div class="table-responsive">
                <table class="table table-striped table-sm">
                  <thead>
                    <tr>
                      <th>{{ __('Grupo') }}</th>
                      <th>{{ __('Alimento') }}</th>
                      <th class="text-right">{{ __('Porción (g)') }}</th>
                      <th class="text-right">{{ __('Energía (kcal)') }}</th>
                      <th class="text-right">{{ __('Proteínas (g)') }}</th>
                      <th class="text-right">{{ __('Grasas (g)') }}</th>
                      <th class="text-right">{{ __('Grasa saturada (g)') }}</th>
                      <th class="text-right">{{ __('Carbohidratos (g)') }}</th>
                      <th class="text-right">{{ __('Índice glicémico') }}</th>
                      <th>{{ __('Alérgenos') }}</th>
                      <th class="text-right">{{ __('Precio (CLP)') }}</th>
                      @can('create', \App\Models\Food::class)
                        <th class="text-right">{{ __('Acciones') }}</th>
                      @endcan
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($foods as $food)
                      <tr>
                        <td>{{ $food->item }}</td>
                        <td class="text-wrap" style="min-width: 180px;">
                          {{ $food->name ?: $food->item }}
                          @foreach ($flags[$food->id] ?? [] as $message)
                            <p class="text-xs text-warning mb-0"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{ $message }}</p>
                          @endforeach
                        </td>
                        <td class="text-right">{{ $number($food->gr) }}</td>
                        <td class="text-right">{{ $number($food->kcal) }}</td>
                        <td class="text-right">{{ $number($food->protein) }}</td>
                        <td class="text-right">{{ $number($food->lipid) }}</td>
                        <td class="text-right">{{ $number($food->saturated_fat) }}</td>
                        <td class="text-right">{{ $number($food->cho) }}</td>
                        <td class="text-right">{{ $food->glycemic_index ?? '—' }}</td>
                        <td>
                          @forelse (array_filter(array_map('trim', explode(';', (string) $food->allergens))) as $tag)
                            <span class="badge bg-gradient-secondary">{{ $allergens[$tag] ?? $tag }}</span>
                          @empty
                            —
                          @endforelse
                        </td>
                        <td class="text-right">{{ $food->price === null ? '—' : number_format((float) $food->price, 0, ',', '.') }}</td>
                        @can('create', \App\Models\Food::class)
                          <td class="text-right text-nowrap">
                            @can('update', $food)
                              <a href="{{ route('foods.edit', $food) }}" class="btn btn-success btn-sm mb-0" title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}">
                                <i class="fas fa-pen" aria-hidden="true"></i>
                              </a>
                            @endcan
                            @can('retire', $food)
                              <form action="{{ route('foods.retire', $food) }}" method="post" style="display:inline-block;"
                                onsubmit="return confirm(@js(__('¿Retirar :name del catálogo? Dejará de usarse en propuestas nuevas; los menús guardados no cambian.', ['name' => $food->name])))">
                                @csrf
                                @method('patch')
                                <button type="submit" class="btn btn-danger btn-sm mb-0" title="{{ __('Retirar') }}" aria-label="{{ __('Retirar') }}">
                                  <i class="fas fa-archive" aria-hidden="true"></i>
                                </button>
                              </form>
                            @endcan
                          </td>
                        @endcan
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
