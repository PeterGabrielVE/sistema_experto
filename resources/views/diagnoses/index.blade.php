@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Crear diagnóstico',
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
                                <a href="{{ route('user.index') }}" class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('patient.store') }}" autocomplete="off"
                            enctype="multipart/form-data">
                            @csrf

                            <h6 class="heading-small text-muted mb-4">{{ __('Diagnosticando al paciente') }}: {{ $patient->first_name }} {{ $patient->last_name }}</h6>
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-name">{{ __('Indice de Insulina') }}</label>
                                        <input type="text" name="first_name" id="input-first_name" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Indice de Insulina') }}" value="{{ old('first_name') }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'first_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('weight') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-weight">{{ __('Peso') }}</label>
                                        <input type="text" name="weight" id="input-weight" class="form-control{{ $errors->has('weight') ? ' is-invalid' : '' }}" placeholder="{{ __('Peso') }}" value="{{ old('weight') }}" required autofocus onchange="calcularIMC()">

                                        @include('alerts.feedback', ['field' => 'last_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Talla') }}</label>
                                        <input type="text" name="size" id="input-size" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">

                                        @include('alerts.feedback', ['field' => 'size'])
                                    </div>
                                    
                                    <div class="form-group{{ $errors->has('birthdate') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-birthdate">{{ __('Nivel Actividad Física') }}</label>
                                        {!! Form::select('gender', [0=>'Muy Ligera',1=>'Ligera',2=>'Moderada',3=>'Activa',4=>'Muy Activa'], null, ['class' => 'form-control','required','id'=>'input-gender','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'birthdate'])
                                    </div>
                                    <div class="form-group{{ $errors->has('gender') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-gender">{{ __('Horario de Comida') }}</label>
                                        {!! Form::select('gender', ['H'=>'Horario 1','M'=>'Horario 2'], null, ['class' => 'form-control','required','id'=>'input-gender','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'gender'])
                                    </div>
                                    <div class="form-group{{ $errors->has('gender') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-gender">{{ __('Jornada Laboral') }}</label>
                                        {!! Form::select('gender', ['H'=>'Horario 1','M'=>'Horario 2'], null, ['class' => 'form-control','required','id'=>'input-gender','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'gender'])
                                    </div>
                                    <div class="form-group{{ $errors->has('gender') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-gender">{{ __('Horario Actividad') }}</label>
                                        {!! Form::select('gender', ['H'=>'Horario 1','M'=>'Horario 2'], null, ['class' => 'form-control','required','id'=>'input-gender','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'gender'])
                                    </div>
                                    <?php 
                                        $nacimiento = new DateTime($patient->birthdate);
                                        $ahora = new DateTime(date("Y-m-d"));
                                        $diferencia = $ahora->diff($nacimiento);
                                        $edad =  $diferencia->format("%y");
                                    ?>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-address">{{ __('Edad') }}</label>
                                        <input type="text" name="address" id="input-address" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('Edad') }}" value="{{ $edad }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-caloria">{{ __('Ingesta Calorica Diaria') }}</label>
                                        <input type="text" name="caloria" id="input-caloria" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('Calorìa') }}" value="{{ old('caloria') }}" required autofocus>
                                        <span class="text-danger" id="error_ingesta" hidden>Ingrese la Ingesta</span>
                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-imc">{{ __('IMC') }}</label>
                                        <input type="text" name="imc" id="input-imc" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('IMC') }}" value="{{ old('address') }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <a onclick="diagnosticar()" class="btn btn-info mt-4">{{ __('Diagnosticar') }}</a>
                                    <!--<button type="submit" class="btn btn-info mt-4">{{ __('Diagnosticar') }}</button>-->
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function calcularIMC(){

            let weight = $('#input-weight').val();
            let size = $('#input-size').val();
            let imc = 0;
            let result = 0;

            if(weight != '' && size != ''){
                imc = weight / Math.pow(size / 100,2);
                result = Math.round(imc * 100) / 100;
            }else{
                result = 0;
            }

            $('#input-imc').val(result)
        }

        function diagnosticar(){

            let ingesta = $('#input-caloria').val();

            if(ingesta === '' || ingesta === null){
                document.getElementById("input-caloria").focus();
                document.getElementById('error_ingesta').removeAttribute("hidden");
            }else{
                let carbohidrato = parseFloat(ingesta) * 0.50;
                let gramoCarbohidato = parseFloat(carbohidrato)/4;

                let isocalorico = parseFloat(carbohidrato)/3;

                let grasa = parseFloat(ingesta) * 0.50;
                let gramoGrasa = parseFloat(grasa)/9;

                let proteina = parseFloat(ingesta) * 0.20;
                let gramoProteina= parseFloat(proteina)/4;

                document.getElementById("error_ingesta").setAttribute("hidden",true);
                
            }
            
        }
    </script>
@endsection