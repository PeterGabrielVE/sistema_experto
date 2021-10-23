@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Crear Horario',
    'activePage' => 'schedule',
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
                                <h3 class="mb-0">{{ __('Gestión de horarios') }}</h3>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('schedule.index') }}" class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('schedule.store') }}" autocomplete="off"
                            enctype="multipart/form-data">
                            @csrf

                            <h6 class="heading-small text-muted mb-4">{{ __('Descripción de horarios') }}</h6>
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="form-group{{ $errors->has('first_breakfast') ? ' has-danger' : '' }} col-6">
                                        <label class="form-control-label" for="input-breakfast">{{ __('Desayuno') }}</label>
                                        <input type="text" name="first_breakfast" id="input-first_breakfast" class="form-control{{ $errors->has('first_breakfast') ? ' is-invalid' : '' }}" placeholder="{{ __('Desayuno') }}" value="{{ old('first_breakfast') }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'first_breakfast'])
                                    </div>
                                    <div class="form-group{{ $errors->has('last_lunch') ? ' has-danger' : '' }} col-6">
                                        <label class="form-control-label" for="input-lunch">{{ __('Almuerzo') }}</label>
                                        <input type="text" name="last_lunch" id="input-last_lunch" class="form-control{{ $errors->has('last_lunch') ? ' is-invalid' : '' }}" placeholder="{{ __('Almuerzo') }}" value="{{ old('last_lunch') }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'last_lunch'])
                                    </div>
                                    <div class="form-group{{ $errors->has('dinner') ? ' has-danger' : '' }} col-12">
                                        <label class="form-control-label" for="input-dinner">{{ __('Cena') }}</label>
                                        <input type="text" name="dinner" id="input-dinner" class="form-control{{ $errors->has('dinner') ? ' is-invalid' : '' }}" placeholder="{{ __('Cena') }}" value="{{ old('dinner') }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'dinner'])

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
