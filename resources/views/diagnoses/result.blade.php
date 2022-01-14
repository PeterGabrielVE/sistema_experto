@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Resultado del Paciente',
    'activePage' => 'result',
    'activeNav' => '',
])
<link rel="stylesheet" type="text/css" href="{{ asset('css/semantic.min.css') }}">
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
                                        <input type="date" name="birthdate" id="input-birthdate" class="form-control{{ $errors->has('birthdate') ? ' is-invalid' : '' }}" placeholder="{{ __('Fecha de Nacimiento') }}" value="{{ $patient->birthdate ?? null }}" required autofocus>

                                        @include('alerts.feedback', ['field' => 'birthdate'])
                                    </div>
                                    <div class="form-group{{ $errors->has('gender') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-gender">{{ __('Sexo') }}</label>
                                        {!! Form::select('gender', ['H'=>'Hombre','M'=>'Mujer'], $patient->gender ?? null, ['class' => 'form-control','required','id'=>'input-gender','autofocus']) !!}

                                        @include('alerts.feedback', ['field' => 'gender'])
                                    </div>
                                    <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-name">{{ __('Indice de Insulina') }}</label>
                                        <input type="text" name="first_name" id="input-indice-insulina" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Indice de Insulina') }}" value="{{ $diagnosis->insulin_index ?? null }}">

                                        @include('alerts.feedback', ['field' => 'first_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('weight') ? ' has-danger' : '' }} col-1">
                                        <label class="form-control-label" for="input-weight">{{ __('Peso') }}</label>
                                        <div class="ui right labeled input">
                                        <input type="text" name="weight" id="input-weight" class="form-control{{ $errors->has('weight') ? ' is-invalid' : '' }}" placeholder="{{ __('Peso') }}" value="{{ $diagnosis->weight ?? null }}">
                                            <div class="ui basic label">Kg.</div>
                                        </div>
                                        @include('alerts.feedback', ['field' => 'last_name'])
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-1 mr-4">
                                        <label class="form-control-label" for="input-size">{{ __('Talla') }}</label>
                                        <div class="ui right labeled input">
                                        <input type="text" name="size" id="input-size" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->size ?? null }}">
                                            <div class="ui basic label">cm.</div>
                                        </div>
                                        @include('alerts.feedback', ['field' => 'size'])
                                    </div>

                                    <div class="form-group{{ $errors->has('birthdate') ? ' has-danger' : '' }} col-2 ml-4">
                                        <label class="form-control-label" for="input-birthdate">{{ __('Nivel Actividad Física') }}</label>
                                        {!! Form::select('physical_activity', [0=>'Muy Ligera',1=>'Ligera',2=>'Moderada',3=>'Activa',4=>'Muy Activa'], $diagnosis->physical_activity ?? null, ['class' => 'form-control','required','id'=>'input-physical-activity']) !!}

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
                                        <label class="form-control-label" for="input-imc_desired">{{ __('IMC Deseado') }}</label>
                                        <input type="text" name="imc_desired" id="input-imc_desired" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" placeholder="{{ __('IMC Deseado') }}" value="{{ $diagnosis->imc_desired ?? null }}">

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('address') ? ' has-danger' : '' }} col-2">
                                        <label class="form-control-label" for="input-imc_desired">{{ __('Método del Pulgar') }}</label>
                                        <input type="text" name="result_pulgar" id="input-result_pulgar" class="form-control{{ $errors->has('result_pulgar') ? ' is-invalid' : '' }}" placeholder="{{ __('Metodo del Pulgar') }}" value="{{ $diagnosis->result_pulgar ?? null }}">

                                        @include('alerts.feedback', ['field' => 'address'])
                                    </div>
                                    <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-name">{{ __('Carbohidrato') }}</label>
                                        <div class="ui right labeled input">
                                        <input type="text" name="carbohydrate" id="input-carbohidrato" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Indice de Insulina') }}" value="{{ $diagnosis->carbohydrate ?? null }}" required autofocus>
                                            <div class="ui basic label">gr</div>
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('weight') ? ' has-danger' : '' }} col-3">
                                            <label class="form-control-label" for="input-weight">{{ __('Isocalorico Carbohidrato') }}</label>
                                            <div class="ui right labeled input">
                                            <input type="text" name="isocaloric_carbohydrate" id="input-isocalorico" class="form-control{{ $errors->has('weight') ? ' is-invalid' : '' }}" placeholder="{{ __('Peso') }}" value="{{ $diagnosis->isocaloric_carbohydrate ?? null }}" required autofocus onchange="calcularIMC()">
                                            <div class="ui basic label">gr</div>
                                            </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Lipido') }}</label><br>
                                        <div class="ui right labeled input">
                                        <input type="text" name="lipido" id="input-lipido" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->carbohydrate ?? null }}" required autofocus onchange="calcularIMC()">
                                        <div class="ui basic label">gr</div>
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Isocalorico Lípido') }}</label>
                                        <div class="ui right labeled input">
                                        <input type="text" name="isocaloric_lipido" id="input-isocalorico2" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->isocaloric_lipido ?? null }}" required autofocus onchange="calcularIMC()">
                                        <div class="ui basic label">gr</div>
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Proteina') }}</label><br>
                                        <div class="ui right labeled input">
                                        <input type="text" name="protein" id="input-proteina" class="form-control{{ $errors->has('protein') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->protein ?? null }}" required autofocus onchange="calcularIMC()">
                                        <div class="ui basic label">gr</div>
                                        </div>
                                    
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Isocalorico Proteína') }}</label>
                                        <div class="ui right labeled input">
                                        <input type="text" name="isocaloric_protein" id="input-isocalorico3" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ $diagnosis->isocaloric_protein ?? null }}" required autofocus onchange="calcularIMC()">
                                        <div class="ui basic label">gr</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    @if($diagnosis->imc < 18.4)
                                    <div class="alert alert-warning alert-with-icon" data-notify="container">
                                        <span data-notify="message">El paciente posee una desnutrición.</span>
                                    </div>
                                    @elseif($diagnosis->imc >= 18.5 && $diagnosis->imc <= 24.9)
                                    <div class="alert alert-success alert-with-icon" data-notify="container">
                                        <span data-notify="message">El paciente posee peso normal.</span>
                                    </div>
                                    @elseif($diagnosis->imc >= 25 && $diagnosis->imc <= 29.9)
                                    <div class="alert alert-warning alert-with-icon">
                                        <span data-notify="message">El paciente posee sobrepeso.
                                        Su peso es algo elevado. Pero con una práctica asidua de ejercicio y un cambio en los hábitos de alimentación, seguro que en pocas semanas consigue mantenerlo a raya. ¡Puedes conseguir su peso ideal!</span>
                                    </div>
                                    @else($diagnosis->imc =>30)
                                    <div class="alert alert-danger alert-with-icon" data-notify="container">
                                        <span data-notify="message">El paciente posee problema de obesidad.</span>
                                    </div>

                                    @endif
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-12">
                                            <h4 class="m-3 p-3">Desayuno</h4><br>
                                        </div>
                                        <div class="col-6">
                                            <table id="example" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Alimento</th>
                                                        <th>Carbohidrato Gr.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($cereales as $c)
                                                    @if($c->cho != 0 && $c->cho > 1)
                                                    <tr>
                                                        <td>{{ $c->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $c->cho,$c->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-6">
                                            <table id="example1" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Alimento</th>
                                                        <th>Gr.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lacteos as $l)
                                                    @if($l->cho != 0 && $l->cho > 1)
                                                    <tr>
                                                        <td>{{ $l->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $l->cho,$c->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_prot($diagnosis->isocaloric_protein, $l->protein,$c->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_lip($diagnosis->isocaloric_lipido, $l->lipid,$c->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        
                                    </div>
                                    <div class="row text-center m-2" style="text-align:center !important; margin:auto;">
                                    <div class="col-12">
                                        <h4 class="m-3 p-3">Almuerzo</h4><br>
                                    </div>
                                    <div class="col-6">
                                            <table id="example2" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Alimento</th>
                                                        <th>Carbohidrato Gr.</th>
                                                        <th>Proteína Gr.</th>
                                                        <th>Lipído Gr.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($cereal_leg as $c)
                                                    @if($c->cho != 0 && $c->cho > 1)
                                                    <tr>
                                                        <td>{{ $c->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $c->cho,$c->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_prot($diagnosis->isocaloric_protein, $c->protein,$c->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_lip($diagnosis->isocaloric_lipido, $c->lipid,$c->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-6">
                                            <table id="example3" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Alimento</th>
                                                        <th>Carbohidrato Gr.</th>
                                                        <th>Proteína Gr.</th>
                                                        <th>Lipído Gr.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($verduras as $v)
                                                    @if($v->cho != 0 && $v->cho > 1)
                                                    <tr>
                                                        <td>{{ $v->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $v->cho,$v->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_prot($diagnosis->isocaloric_protein, $v->protein,$v->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_lip($diagnosis->isocaloric_lipido, $v->lipid,$v->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                    </div>
                                    <div class="row text-center m-2" style="text-align:center !important; margin:auto;">
                                        <div class="col-12">
                                            <h4 class="m-3 p-3">Cena</h4><br>
                                        </div>
                                        <div class="col-6">
                                            <table id="example4" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Alimento</th>
                                                        <th>Carbohidrato Gr.</th>
                                                        <th>Proteína Gr.</th>
                                                        <th>Lipído Gr.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($cereal_leg as $c)
                                                    @if($c->cho != 0 && $c->cho > 1)
                                                    <tr>
                                                        <td>{{ $c->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $c->cho,$c->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_prot($diagnosis->isocaloric_protein, $c->protein,$c->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_lip($diagnosis->isocaloric_lipido, $c->lipid,$c->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-6">
                                            <table id="example5" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Alimento</th>
                                                        <th>Carbohidrato Gr.</th>
                                                        <th>Proteína Gr.</th>
                                                        <th>Lipído Gr.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($verduras as $v)
                                                    @if($v->cho != 0 && $v->cho > 1)
                                                    <tr>
                                                        <td>{{ $v->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $v->cho,$v->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_prot($diagnosis->isocaloric_protein, $v->protein,$v->gr),0) }}</td>
                                                        <td>{{ round(regla_tres_lip($diagnosis->isocaloric_lipido, $v->lipid,$v->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                    </div>
                            
                                    
                                </div>
                                
                                <div class="text-center">
                                    <a href="{{ route('download', $diagnosis->id) }}" class="btn btn-info mt-4">{{ __('Descargar') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
<script>
        $(document).ready(function() {
            var table = $('#example,#example1,#example2,#example3,#example4,#example5').DataTable({
            "dom": '<"float-left"><"float-right">t<"float-left"l><"float-right"p><"clearfix">',
            "responsive": true,
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
            },
            "order": [[ 0, "desc" ]],
            "initComplete": function () {
                this.api().columns().every( function () {
                    var that = this;

                    $( 'input', this.footer() ).on( 'keyup change', function () {
                        if ( that.search() !== this.value ) {
                            that
                                .search( this.value )
                                .draw();
                            }
                    });
                })
            }
    });

});
</script>
@endpush