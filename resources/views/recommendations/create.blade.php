@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Crear recomendacion',
    'activePage' => 'recommendation',
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
                                <h3 class="mb-0">{{ __('Gestión de recomendaciones') }}</h3>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('recommendation.index') }}" class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('recommendation.store') }}" autocomplete="off"
                            enctype="multipart/form-data">
                            @csrf

                            <h6 class="heading-small text-muted mb-4">{{ __('Descripción de la recomendación') }}</h6>
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="form-group{{ $errors->has('description') ? ' has-danger' : '' }} col-12">
                                        <label class="form-control-label" for="input-description">{{ __('Clasificación') }}</label>
                                        {!! Form::select('id_rule', $options, null, ['class' => 'form-control','required','id'=>'id_rule','autofocus']) !!}
                                        @include('alerts.feedback', ['field' => 'description'])
                                    </div>

                                    <div class="form-group{{ $errors->has('description') ? ' has-danger' : '' }} col-12">
                                        <label class="form-control-label" for="input-description">{{ __('Descripción') }}</label>
                                        <input type="text" name="description" id="input-comment" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" placeholder="{{ __('Descripción') }}" value="{{ old('') }}" required autofocus>

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
