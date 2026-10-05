@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => $rule->exists ? 'Editar regla' : 'Nueva regla',
    'activePage' => 'macro-rules',
    'activeNav' => '',
])

@php
    $variables = \App\Models\MacroRule::variables();
    $symbols = ['>' => '>', '>=' => '≥', '<' => '<', '<=' => '≤'];
    $actions = old('actions', $rule->actions ?? []);
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
                                <h3 class="mb-0">{{ $rule->exists ? __('Editar regla :code', ['code' => $rule->code()]) : __('Nueva regla de macronutrientes') }}</h3>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('macro-rules.index') }}" class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('alerts.errors')
                        <form method="post" action="{{ $rule->exists ? route('macro-rules.update', $rule) : route('macro-rules.store') }}" autocomplete="off">
                            @csrf
                            @if ($rule->exists)
                                @method('put')
                            @endif

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="form-control-label" for="input-name">{{ __('Nombre') }}</label>
                                    <input type="text" name="name" id="input-name" maxlength="120" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" value="{{ old('name', $rule->name) }}" required>
                                    @include('alerts.feedback', ['field' => 'name'])
                                </div>
                                <div class="form-group col-md-6 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="active" value="0">
                                        <input class="form-check-input" type="checkbox" name="active" id="input-active" value="1" @checked(old('active', $rule->active))>
                                        <label class="form-check-label" for="input-active">{{ __('Activa') }}</label>
                                    </div>
                                </div>
                            </div>

                            <h6 class="heading-small text-muted mt-3 mb-2">{{ __('Si') }}</h6>
                            <div class="row">
                                <div class="form-group col-md-5">
                                    <label class="form-control-label" for="input-variable">{{ __('Variable') }}</label>
                                    <select name="variable" id="input-variable" class="form-control{{ $errors->has('variable') ? ' is-invalid' : '' }}" required>
                                        <option value="">{{ __('Seleccione...') }}</option>
                                        @foreach ($variables as $key => $variable)
                                            <option value="{{ $key }}" @selected(old('variable', $rule->variable) === $key)>
                                                {{ $variable['label'] }}{{ $variable['unit'] ? " ({$variable['unit']})" : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @include('alerts.feedback', ['field' => 'variable'])
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-control-label" for="input-operator">{{ __('Condición') }}</label>
                                    <select name="operator" id="input-operator" class="form-control{{ $errors->has('operator') ? ' is-invalid' : '' }}" required>
                                        @foreach (\App\Models\MacroRule::operators() as $operator)
                                            <option value="{{ $operator }}" @selected(old('operator', $rule->operator) === $operator)>{{ $symbols[$operator] ?? $operator }}</option>
                                        @endforeach
                                    </select>
                                    @include('alerts.feedback', ['field' => 'operator'])
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label" for="input-value">{{ __('Valor') }}</label>
                                    <input type="number" step="any" name="value" id="input-value" class="form-control{{ $errors->has('value') ? ' is-invalid' : '' }}" value="{{ old('value', $rule->value) }}" required>
                                    @include('alerts.feedback', ['field' => 'value'])
                                </div>
                            </div>
                            <p class="text-xs text-secondary">{{ __('Si el paciente no tiene el dato, la regla no se aplica.') }}</p>

                            <h6 class="heading-small text-muted mt-3 mb-2">{{ __('Entonces') }}</h6>
                            <p class="text-xs text-secondary">{{ __('Complete solo las acciones que la regla debe aplicar. Si varias reglas fijan el mismo valor, gana el más restrictivo (el menor máximo, el mayor mínimo de proteína y de grasas); el ajuste de energía se suma.') }}</p>
                            @error('actions')
                                <p class="text-sm text-danger">{{ $message }}</p>
                            @enderror
                            <div class="row">
                                @foreach (\App\Models\MacroRule::actionSpecs() as $key => $spec)
                                    <div class="form-group col-md-3">
                                        <label class="form-control-label" for="input-action-{{ $key }}">{{ $spec['label'] }}</label>
                                        <div class="input-group">
                                            <input type="number" step="any" min="{{ $spec['range'][0] }}" max="{{ $spec['range'][1] }}"
                                                name="actions[{{ $key }}]" id="input-action-{{ $key }}"
                                                class="form-control{{ $errors->has("actions.$key") ? ' is-invalid' : '' }}" value="{{ $actions[$key] ?? '' }}">
                                            <span class="input-group-text">{{ $spec['unit'] }}</span>
                                        </div>
                                        <small class="text-muted">{{ $spec['range'][0] }} a {{ $spec['range'][1] }}</small>
                                        @include('alerts.feedback', ['field' => "actions.$key"])
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-group">
                                <label class="form-control-label" for="input-advice">{{ __('Indicación para el profesional (opcional)') }}</label>
                                <textarea name="advice" id="input-advice" rows="2" maxlength="500" class="form-control{{ $errors->has('advice') ? ' is-invalid' : '' }}">{{ old('advice', $rule->advice) }}</textarea>
                                @include('alerts.feedback', ['field' => 'advice'])
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-info mt-4">{{ __('Guardar') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
