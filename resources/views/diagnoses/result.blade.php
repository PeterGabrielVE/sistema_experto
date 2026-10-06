@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Resultado del Paciente',
    'activePage' => 'result',
    'activeNav' => '',
])
@section('content')
    <div class="panel-header panel-header-sm">
    </div>
    <div class="content"  style="display: flex;height: 100%;">
        <div class="row">
            <div class="col-xl-12 order-xl-1">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h3 class="mb-0">{{ __('Gestión de paciente') }}</h3>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('diagnosis.all',$patient->id) }}"  class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('patient.store') }}" autocomplete="off"
                            enctype="multipart/form-data">
                            @csrf

                            <h6 class="heading-small text-muted mb-4">{{ __('Resultado del paciente') }}</h6>
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-name">{{ __('Nombre') }}</label>
                                        <input type="text" name="first_name" id="input-first_name" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Nombre') }}" value="{{ $patient->first_name ?? null }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'first_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('last_name') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-name">{{ __('Apellido') }}</label>
                                        <input type="text" name="last_name" id="input-last_name" class="form-control{{ $errors->has('last_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Apellido') }}" value="{{ $patient->last_name ?? null }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'last_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('birthdate') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-birthdate">{{ __('Fecha de Nacimiento') }}</label>
                                        <input type="date" name="birthdate" id="input-birthdate" class="form-control{{ $errors->has('birthdate') ? ' is-invalid' : '' }}" placeholder="{{ __('Fecha de Nacimiento') }}" value="{{ $patient->birthdate?->toDateString() }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'birthdate'])
                                    </div>
                                    <div class="form-group{{ $errors->has('gender') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-gender">{{ __('Sexo') }}</label>
                                        <x-select name="gender" :options="['H'=>'Hombre','M'=>'Mujer']" :selected="$patient->gender ?? null" class="form-control" required id="input-gender" autofocus />

                                        @include('alerts.feedback', ['field' => 'gender'])
                                    </div>
                                    <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-name">{{ __('Indice de Insulina') }}</label>
                                        <input type="text" name="first_name" id="input-indice-insulina" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Indice de Insulina') }}" value="{{ $diagnosis->insulin_index ?? null }}">

                                        @include('alerts.feedback', ['field' => 'first_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('weight') ? ' has-danger' : '' }} col-1">
                                        <label class="form-control-label" for="input-weight">{{ __('Peso') }}</label>
                                        <div class="input-group">
                                        <input type="text" name="weight" id="input-weight" class="form-control{{ $errors->has('weight') ? ' is-invalid' : '' }}" placeholder="{{ __('Peso') }}" value="{{ $diagnosis->weight ?? null }}">
                                            <span class="input-group-text">Kg.</span>
                                        </div>
                                        @include('alerts.feedback', ['field' => 'last_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-1 mr-4">
                                        <label class="form-control-label" for="input-size">{{ __('Talla') }}</label>
                                        <div class="input-group">
                                        <input type="text" name="size" id="input-size" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->size ?? null }}">
                                            <span class="input-group-text">cm.</span>
                                        </div>
                                        @include('alerts.feedback', ['field' => 'size'])
                                    </div>

                                    <div class="form-group{{ $errors->has('birthdate') ? ' has-danger' : '' }} col-2 ml-4">
                                        <label class="form-control-label" for="input-birthdate">{{ __('Nivel Actividad Física') }}</label>
                                        <x-select name="physical_activity" :options="[0=>'Muy Ligera',1=>'Ligera',2=>'Moderada',3=>'Activa',4=>'Muy Activa']" :selected="$diagnosis->physical_activity ?? null" class="form-control" required id="input-physical-activity" />

                                        @include('alerts.feedback', ['field' => 'birthdate'])
                                    </div>

                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-age">{{ __('Edad') }}</label>
                                        <input type="text" name="age" id="input-age" class="form-control{{ $errors->has('age') ? ' is-invalid' : '' }}" placeholder="{{ __('Edad') }}" value="{{ $diagnosis->age ?? null }}">
                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-imc">{{ __('IMC') }}</label>
                                        <input type="text" name="imc" id="input-imc" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('IMC') }}" value="{{ $diagnosis->imc ?? null }}">

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-imc_desired">{{ __('Factor de Corrección (kcal/kg)') }}</label>
                                        <input type="text" name="imc_desired" id="input-imc_desired" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('Factor de Corrección') }}" value="{{ $diagnosis->imc_desired ?? null }}">

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-result_pulgar">{{ __('Requerimiento (kcal/día)') }}</label>
                                        <input type="text" name="result_pulgar" id="input-result_pulgar" class="form-control{{ $errors->has('result_pulgar') ? ' is-invalid' : '' }}" placeholder="{{ __('Requerimiento energético') }}" value="{{ $diagnosis->result_pulgar ?? null }}">

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-name">{{ __('Carbohidrato') }}</label>
                                        <div class="input-group">
                                        <input type="text" name="carbohydrate" id="input-carbohidrato" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Indice de Insulina') }}" value="{{ $diagnosis->carbohydrate ?? null }}" required autofocus>
                                            <span class="input-group-text">gr</span>
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('weight') ? ' has-danger' : '' }} col-3">
                                            <label class="form-control-label" for="input-weight">{{ __('Isoglucídico') }}</label>
                                            <div class="input-group">
                                            <input type="text" name="isocaloric_carbohydrate" id="input-isocalorico" class="form-control{{ $errors->has('weight') ? ' is-invalid' : '' }}" placeholder="{{ __('Peso') }}" value="{{ $diagnosis->isocaloric_carbohydrate ?? null }}" required autofocus onchange="calcularIMC()">
                                            <span class="input-group-text">gr</span>
                                            </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Lipido') }}</label><br>
                                        <div class="input-group">
                                        <input type="text" name="lipido" id="input-lipido" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->lipido ?? null }}" required autofocus onchange="calcularIMC()">
                                        <span class="input-group-text">gr</span>
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Isocalorico Lípido') }}</label>
                                        <div class="input-group">
                                        <input type="text" name="isocaloric_lipido" id="input-isocalorico2" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->isocaloric_lipido ?? null }}" required autofocus onchange="calcularIMC()">
                                        <span class="input-group-text">gr</span>
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Proteina') }}</label><br>
                                        <div class="input-group">
                                        <input type="text" name="protein" id="input-proteina" class="form-control{{ $errors->has('protein') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->protein ?? null }}" required autofocus onchange="calcularIMC()">
                                        <span class="input-group-text">gr</span>
                                        </div>
                                    
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Isoproteico') }}</label>
                                        <div class="input-group">
                                        <input type="text" name="isocaloric_protein" id="input-isocalorico3" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->isocaloric_protein ?? null }}" required autofocus onchange="calcularIMC()">
                                        <span class="input-group-text">gr</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    @if($rule == 1)
                                    <div class="alert alert-warning alert-with-icon" data-notify="container">
                                        <span data-notify="message">El paciente posee una desnutrición.</span>
                                    </div>
                                    @elseif($rule == 2)
                                    <div class="alert alert-success alert-with-icon" data-notify="container">
                                        <span data-notify="message">El paciente posee peso normal.</span>
                                    </div>
                                    @elseif($rule == 3)
                                    <div class="alert alert-warning alert-with-icon">
                                        <span data-notify="message">El paciente posee sobrepeso.
                                        Su peso es algo elevado. Pero con una práctica asidua de ejercicio y un cambio en los hábitos de alimentación, seguro que en pocas semanas consigue mantenerlo a raya. ¡Puedes conseguir su peso ideal!</span>
                                    </div>
                                    @else
                                    <div class="alert alert-danger alert-with-icon" data-notify="container">
                                        <span data-notify="message">El paciente posee problema de obesidad.</span>
                                    </div>

                                    @endif
                                    </div>
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                @switch($diagnosis->inference_source)
                                                    @case('ml')
                                                        Clasificado por modelo ML (confianza {{ number_format($diagnosis->inference_confidence * 100, 1) }}%, versión {{ $diagnosis->model_version }})
                                                        @break
                                                    @case('manual')
                                                        Categoría confirmada por un doctor
                                                        @break
                                                    @default
                                                        Clasificado por reglas de IMC
                                                @endswitch
                                            </small>
                                        </div>
                                        @if(auth()->user()->can('confirmCategory', $diagnosis))
                                        {{-- Fields belong to #rule-form (outside the page form) via the form attribute. --}}
                                        <div class="form-group col-md-4">
                                            <label class="form-control-label" for="input-id-rule">{{ __('Corregir categoría') }}</label>
                                            <x-select name="id_rule" :options="$categories" :selected="$rule" class="form-control" id="input-id-rule" form="rule-form" />
                                        </div>
                                        <div class="form-group col-md-2">
                                            <button type="submit" form="rule-form" class="btn btn-primary btn-round">{{ __('Confirmar') }}</button>
                                        </div>
                                        @endif
                                    </div>
                                    @include('diagnoses._meal-plan')
                                </div>

                                <div class="text-center">
                                    <a href="{{ route('download', ['diagnosis' => $diagnosis, 'variante' => $variant ?: null]) }}" class="btn btn-info mt-4" target="_blank">{{ __('Descargar') }}</a>
                                </div>
                            </div>
                        </form>
                        <form id="rule-form" method="post" action="{{ route('diagnosis.rule', $diagnosis) }}">
                            @csrf
                            @method('PUT')
                        </form>

                        @can('viewClinicalRecord', $patient)
                            @includeWhen($expert, 'diagnoses._expert-evaluation')
                            @include('diagnoses._clinical-data')
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
