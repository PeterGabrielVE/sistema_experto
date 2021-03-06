@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Crear Consulta',
    'activePage' => 'patient',
    'activeNav' => '',
])

@include('diagnoses.create')
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
                                <a href="{{ route('diagnosis.all',$patient->id) }}" class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('patient.store') }}" autocomplete="off"
                            enctype="multipart/form-data">
                            @csrf

                            <h6 class="heading-small text-muted mb-4">{{ __('Consultando al paciente') }}: {{ $patient->first_name }} {{ $patient->last_name }}</h6>
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-name">{{ __('Indice de Insulina') }}</label>
                                        <input type="text" name="first_name" id="input-indice-insulina" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Indice de Insulina') }}" value="{{ old('first_name') }}" required autofocus>

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
                                        {!! Form::select('physical_activity', [0=>'Muy Ligera',1=>'Ligera',2=>'Moderada',3=>'Activa',4=>'Muy Activa'], null, ['class' => 'form-control','required','id'=>'input-physical-activity','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'birthdate'])
                                    </div>


                                    <?php
                                        $nacimiento = new DateTime($patient->birthdate);
                                        $ahora = new DateTime(date("Y-m-d"));
                                        $diferencia = $ahora->diff($nacimiento);
                                        $edad =  $diferencia->format("%y");
                                    ?>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-age">{{ __('Edad') }}</label>
                                        <input type="text" name="address" id="input-age" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('Edad') }}" value="{{ $edad }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-imc">{{ __('IMC') }}</label>
                                        <input type="text" name="imc" id="input-imc" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('IMC') }}" value="{{ old('address') }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                </div>

                                <div class="text-center">
                                    @if(Auth::user()->rol_id == 2 || Auth::user()->rol_id == 3)
                                    <a onclick="diagnosticar()" class="btn btn-info mt-4">{{ __('Realizar consultar') }}</a>
                                    @endif
                                </div>
                            </div>
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

            $('#exampleModal').modal('show');

            /*if(ingesta === '' || ingesta === null){
                document.getElementById("input-caloria").focus();
                document.getElementById('error_ingesta').removeAttribute("hidden");
            }else{*/

                //document.getElementById("error_ingesta").setAttribute("hidden",true);

                let imc = parseFloat($('#input-imc').val());
                let imc_deseado = 0;

                if(imc < 18.4){
                    imc_deseado = 35;
                }else if(imc >= 18.4 && imc <= 24.9){
                    imc_deseado = 30;
                }else if(imc >= 25 && imc <= 29.9){
                    imc_deseado = 25;
                }else{
                    imc_deseado = 30;
                }

                let size = $('#input-size').val();
                let peso = parseFloat($('#input-weight').val());
                let edad = $('#input-age').val();
                let sexo = '{{ $patient->gender }}';

                let pulgada = parseFloat(size)/2.54;
                let pie = pulgada/12;

                let pesoStone = parseFloat(peso)/6.35;
                let pesoLbs = parseFloat(peso)*2.20;

                let x = calculateAge(edad);
                let y = calculateWeight(pesoStone, pesoLbs);
                let z = calculateHeight(pie, pulgada);

                let tmb = finalResult(x, y, z, sexo);


                let peso_deseado = (size*size)*imc_deseado;

                let factor = $('#input-physical-activity').val();

                /*(factor) {
                case 0:
                    tmb = tmb * 1.2;
                    break;
                case 1:
                    tmb = tmb * 1.375;
                    break;
                case 2:
                    tmb = tmb * 1.55;
                    break;
                case 3:
                    tmb = tmb * 1.925;
                    break;
                case 4:
                    tmb = tmb * 1.9;
                    break;
                default:
                    break;
                }*/
                let result_pulgar = peso * imc_deseado;


                let carbohidrato = parseFloat(result_pulgar) * 0.50;
                let gramoCarbohidato = parseFloat(carbohidrato)/4;

                let grasa = parseFloat(result_pulgar) * 0.50;
                let gramoGrasa = parseFloat(grasa)/9;

                let proteina = parseFloat(result_pulgar) * 0.20;
                let gramoProteina= parseFloat(proteina)/4;

                let isocal1 = parseFloat(gramoCarbohidato)/3;
                let isocal2 = parseFloat(gramoGrasa)/3;
                let isocal3 = parseFloat(gramoProteina)/3;

                $('#input-carbohidrato').val(gramoCarbohidato.toFixed(2));

                $('#input-isocalorico').val(isocal1.toFixed(2));
                $('#input-isocalorico2').val(isocal2.toFixed(2));
                $('#input-isocalorico3').val(isocal3.toFixed(2));

                $('#input-lipido').val(gramoGrasa.toFixed(2));
                $('#input-proteina').val(gramoProteina.toFixed(2));

                $('#input-imc-deseado').val(imc_deseado.toFixed(2));
                $('#input-result-pulgar').val(result_pulgar.toFixed(2));

                copy();
            //}

        }

        function copy(){
                let insulina = $('#input-indice-insulina').val();
                let weight= $('#input-weight').val();
                let size = $('#input-size').val();

                let actividad_fisica = $('#input-physical-activity').val();
                let edad = $('#input-age').val();
                let ingesta = $('#input-caloria').val();
                let imc = $('#input-imc').val();

                $('#indice-insulina').val(insulina);
                $('#talla').val(size);
                $('#peso').val(weight);

                $('#actividad_fisica').val(actividad_fisica);
                $('#edad').val(edad);
                $('#ingesta_calorica').val(ingesta);
                $('#imc').val(imc);


            }

            function calculateAge(age){
                finalAge = age * 5;
                return finalAge;
            }

            function calculateHeight(heightFeet, heightInches){
                let centimeterHeight = ((heightFeet * 12) + heightInches) * 2.54;
                let finalHeight = centimeterHeight * 6.25;
                return finalHeight;
            }

            function calculateWeight(weightStone, weightLbs){
                let kilogramWeight = ((weightStone * 14) + weightLbs) * 0.453;
                let finalWeight = kilogramWeight * 10;
                return finalWeight;
            }

            function finalResult(x, y, z, gender){
                    let result = z + y - x;
                    let resultFinal = 0;
                    if(gender === 'H') {
                        return resultFinal = result + 5;
                    } else {
                        return resultFinal = result - 161;

                    }
            }


    </script>
@endsection
