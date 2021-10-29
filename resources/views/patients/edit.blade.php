@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Editar paciente',
    'activePage' => 'patient',
    'activeNav' => '',
])

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
                                <h3 class="mb-0">{{ __('Gestión de paciente') }}</h3>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('patient.index') }}" class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('patient.update',$patient->id) }}" autocomplete="off"
                            enctype="multipart/form-data">
                            @csrf
                            @method('put')
                            <h6 class="heading-small text-muted mb-4">{{ __('Información del paciente') }}</h6>
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-6">
                                        <label class="form-control-label" for="input-name">{{ __('Nombre') }}</label>
                                        <input type="text" name="first_name" id="input-first_name" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Nombre') }}" value="{{ $patient->first_name }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'first_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('last_name') ? ' has-danger' : '' }} col-6">
                                        <label class="form-control-label" for="input-name">{{ __('Apellido') }}</label>
                                        <input type="text" name="last_name" id="input-last_name" class="form-control{{ $errors->has('last_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Apellido') }}" value="{{ $patient->last_name  }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'last_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-12">
                                        <label class="form-control-label" for="input-address">{{ __('Dirección') }}</label>
                                        <input type="text" name="address" id="input-address" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('Dirección') }}" value="{{ $patient->address  }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('id_region') ? ' has-danger' : '' }} col-6">
                                        <label class="form-control-label" for="input-address">{{ __('Región') }}</label>
                                        {!! Form::select('id_region', $regions, $patient->id_region ?? null, ['class' => 'form-control','required','id'=>'id_region','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'id_region'])
                                    </div>
                                    <div class="form-group{{ $errors->has('id_comuna') ? ' has-danger' : '' }} col-6">
                                        <label class="form-control-label" for="input-address">{{ __('Comuna') }}</label>
                                        {!! Form::select('id_comuna', $comunas, $patient->id_comuna ?? nullnull, ['class' => 'form-control','required','id'=>'id_comuna','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'id_comuna'])
                                    </div>
                                    <div class="form-group{{ $errors->has('birthdate') ? ' has-danger' : '' }} col-6">
                                        <label class="form-control-label" for="input-birthdate">{{ __('Fecha de Nacimiento') }}</label>
                                        <input type="date" name="birthdate" id="input-birthdate" class="form-control{{ $errors->has('birthdate') ? ' is-invalid' : '' }}" placeholder="{{ __('Fecha de Nacimiento') }}" value="{{ $patient->birthdate ?? null }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'birthdate'])
                                    </div>
                                    <div class="form-group{{ $errors->has('gender') ? ' has-danger' : '' }} col-6">
                                        <label class="form-control-label" for="input-gender">{{ __('Sexo') }}</label>
                                        {!! Form::select('gender', ['H'=>'Hombre','M'=>'Mujer'], $patient->gender ?? null, ['class' => 'form-control','required','id'=>'input-gender','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'gender'])
                                    </div>
                                    <div class="form-group{{ $errors->has('comment') ? ' has-danger' : '' }} col-12">
                                        <label class="form-control-label" for="input-comment">{{ __('Comentario') }}</label>
                                        <input type="text" name="comment" id="input-comment" class="form-control{{ $errors->has('comment') ? ' is-invalid' : '' }}" placeholder="{{ __('Comentario') }}" value="{{ $patient->comment }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'comment'])
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-info mt-4">{{ __('Guardar') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection