@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => $food->exists ? 'Editar alimento' : 'Nuevo alimento',
    'activePage' => 'foods',
    'activeNav' => '',
])

@php
    // Values as the doctor reads them: decimal comma.
    $value = fn (string $field, ?string $column = null) => old($field, ($v = $food->{$column ?? $field}) === null || $v === ''
        ? ''
        : (is_numeric($n = str_replace(',', '.', (string) $v)) ? str_replace('.', ',', (string) (float) $n) : $v));
    $chosen = old('allergens', array_filter(explode(';', (string) $food->allergens)));
    $numbers = [
        'grams' => ['gr', __('Gramos por porción'), true],
        'kcal' => ['kcal', __('Energía (kcal)'), true],
        'protein' => ['protein', __('Proteínas (g)'), true],
        'fat' => ['lipid', __('Grasas (g)'), true],
        'saturated_fat' => ['saturated_fat', __('Grasa saturada (g)'), false],
        'cho' => ['cho', __('Carbohidratos (g)'), true],
        'glycemic_index' => ['glycemic_index', __('Índice glicémico (glucosa = 100)'), false],
        'price' => ['price', __('Precio por porción (CLP)'), false],
    ];
@endphp

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
                                <h3 class="mb-0">{{ $food->exists ? __('Editar alimento') : __('Nuevo alimento') }}</h3>
                                <p class="text-sm text-secondary mb-0">{{ __('Valores por porción de intercambio. Los cambios se usan en las propuestas nuevas; las ya guardadas no cambian.') }}</p>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('foods.index') }}" class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('alerts.errors')
                        <form method="post" action="{{ $food->exists ? route('foods.update', $food) : route('foods.store') }}" autocomplete="off">
                            @csrf
                            @if ($food->exists)
                                @method('put')
                            @endif

                            <div class="row">
                                <div class="form-group col-md-5">
                                    <label class="form-control-label" for="input-name">{{ __('Nombre') }}</label>
                                    <input type="text" name="name" id="input-name" maxlength="100" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" value="{{ old('name', $food->name) }}" required>
                                    @include('alerts.feedback', ['field' => 'name'])
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="input-group">{{ __('Grupo de intercambio') }}</label>
                                    <select name="group" id="input-group" class="form-control{{ $errors->has('group') ? ' is-invalid' : '' }}" required>
                                        <option value="">{{ __('Seleccione...') }}</option>
                                        @foreach ($groups as $group)
                                            <option value="{{ $group }}" @selected(old('group', $food->item) === $group)>{{ $group }}</option>
                                        @endforeach
                                    </select>
                                    @include('alerts.feedback', ['field' => 'group'])
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label" for="input-portion">{{ __('Porción (texto)') }}</label>
                                    <input type="text" name="portion" id="input-portion" maxlength="50" class="form-control{{ $errors->has('portion') ? ' is-invalid' : '' }}" value="{{ old('portion', $food->portion) }}" placeholder="{{ __('1 taza') }}">
                                    @include('alerts.feedback', ['field' => 'portion'])
                                </div>
                            </div>

                            <div class="row">
                                @foreach ($numbers as $field => [$column, $label, $required])
                                    <div class="form-group col-md-3">
                                        <label class="form-control-label" for="input-{{ $field }}">{{ $label }}</label>
                                        <input type="text" inputmode="decimal" name="{{ $field }}" id="input-{{ $field }}" class="form-control{{ $errors->has($field) ? ' is-invalid' : '' }}" value="{{ $value($field, $column) }}" @required($required)>
                                        @include('alerts.feedback', ['field' => $field])
                                    </div>
                                @endforeach
                            </div>
                            <p class="text-xs text-secondary">{{ __('Deje vacíos el índice glicémico, la grasa saturada o el precio si no los conoce: un valor vacío es "sin dato", no cero.') }}</p>

                            <h6 class="heading-small text-muted mt-3 mb-2">{{ __('Alérgenos') }}</h6>
                            <div class="row">
                                @foreach ($allergens as $key => $label)
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" id="allergen-{{ $key }}" value="{{ $key }}" @checked(in_array($key, $chosen, true))>
                                            <label class="form-check-label" for="allergen-{{ $key }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @include('alerts.feedback', ['field' => 'allergens'])

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success">{{ __('Guardar') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
