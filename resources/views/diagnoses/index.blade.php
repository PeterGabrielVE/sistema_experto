@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Crear Consulta',
    'activePage' => 'patient',
    'activeNav' => '',
])

@include('diagnoses.create')
@php
    // Last weight / height of the clinical measurements registry (may come from different controls).
    $lastWeight = $anthropometry['weight'] ?? null;
    $lastHeight = $anthropometry['height'] ?? null;
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
                                <h3 class="mb-0">{{ __('Gestión de paciente') }}</h3>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('diagnosis.all',$patient->id) }}" class="btn btn-primary btn-round">{{ __('Volver a la lista') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('alerts.errors')
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
                                        <input type="text" name="weight" id="input-weight" class="form-control{{ $errors->has('weight') ? ' is-invalid' : '' }}" placeholder="{{ __('Peso') }}" value="{{ old('weight', $lastWeight?->weight_kg) }}" required autofocus onchange="calcularIMC()">
                                        @if($lastWeight)
                                            <small class="text-muted">{{ __('Medición del :date', ['date' => $lastWeight->measured_at->format('d/m/Y')]) }}</small>
                                        @endif

                                        @include('alerts.feedback', ['field' => 'weight'])
                                    </div>
                                    <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-size">{{ __('Talla') }}</label>
                                        <input type="text" name="size" id="input-size" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('size', $lastHeight?->height_cm) }}" required autofocus onchange="calcularIMC()">
                                        @if($lastHeight)
                                            <small class="text-muted">{{ __('Medición del :date', ['date' => $lastHeight->measured_at->format('d/m/Y')]) }}</small>
                                        @endif

                                        @include('alerts.feedback', ['field' => 'size'])
                                    </div>

                                    <div class="form-group{{ $errors->has('birthdate') ? ' has-danger' : '' }} col-3">
                                        <label class="form-control-label" for="input-birthdate">{{ __('Nivel Actividad Física') }}</label>
                                        <x-select name="physical_activity" :options="[0=>'Muy Ligera',1=>'Ligera',2=>'Moderada',3=>'Activa',4=>'Muy Activa']" class="form-control" required id="input-physical-activity" autofocus />

                                        @include('alerts.feedback', ['field' => 'birthdate'])
                                    </div>


                                    @php($edad = $patient->age)
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

                                @can('viewClinicalRecord', $patient)
                                    <p class="text-muted small mb-0">
                                        @if($lastWeight || $lastHeight)
                                            {{ __('Peso y talla tomados del registro de mediciones; puede modificarlos.') }}
                                        @else
                                            {{ __('El paciente no tiene mediciones registradas.') }}
                                        @endif
                                        <a href="{{ route('patient.measurements.index', $patient) }}">{{ __('Ver mediciones') }}</a>
                                    </p>
                                @endcan

                                <div class="text-center">
                                    @if(auth()->user()->can('create', \App\Models\Diagnosis::class))
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
        // Weight and height may come pre-filled from the measurements registry.
        document.addEventListener('DOMContentLoaded', calcularIMC);

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

        const MACRO_FIELDS = {
            carbohydrate: '#input-carbohidrato', isocaloric_carbohydrate: '#input-isocalorico',
            lipido: '#input-lipido', isocaloric_lipido: '#input-isocalorico2',
            protein: '#input-proteina', isocaloric_protein: '#input-isocalorico3',
            result_pulgar: '#input-result-pulgar', imc_desired: '#input-imc-deseado',
        };

        // Suggested macronutrient distribution of the expert service (DiagnosisController::macros).
        function diagnosticar(){
            copy();
            $('#exampleModal').modal('show');

            $.each(MACRO_FIELDS, (field, input) => $(input).val(''));
            let summary = $('#macro-plan-summary');
            summary.html($('<p class="text-sm text-secondary mb-0">').text(@json(__('Calculando la distribución de macronutrientes…'))));

            $.ajax({
                url: @json(route('diagnosis.macros', $patient)),
                method: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'},
                data: {
                    weight: $('#input-weight').val(),
                    size: $('#input-size').val(),
                    age: $('#input-age').val(),
                    physical_activity: $('#input-physical-activity').val(),
                },
            }).done(function (response) {
                if (!response.fields) {
                    let reason = response.data && response.data.reason;
                    summary.html($('<p class="text-sm text-warning mb-0">').text(
                        (reason ? reason + ' ' : @json(__('El sistema experto no está disponible.')) + ' ') + @json(__('Ingrese los valores manualmente.'))
                    ));
                    return;
                }
                $.each(MACRO_FIELDS, (field, input) => $(input).val(response.fields[field]));
                summary.html(macroSummary(response.data));
            }).fail(function (xhr) {
                let errors = xhr.responseJSON && xhr.responseJSON.errors;
                summary.html($('<p class="text-sm text-danger mb-0">').text(
                    errors ? Object.values(errors).flat().join(' ') : @json(__('No se pudo calcular la distribución; ingrese los valores manualmente.'))
                ));
            });
        }

        function macroSummary(plan){
            let m = plan.macros, e = plan.energy;
            let box = $('<div class="border border-radius-lg p-3">');
            box.append($('<p class="text-sm mb-1">').append(
                $('<strong>').text(e.target + ' kcal/día'),
                document.createTextNode(` · ${m.carbohydrates.label} ${m.carbohydrates.percent} % · ${m.proteins.label} ${m.proteins.percent} % · ${m.fats.label} ${m.fats.percent} %`)
            ));
            plan.rules.forEach(rule => box.append(
                $('<p class="text-xs mb-0">').append($('<strong>').text(`${rule.rule_id} ${rule.title}: `), document.createTextNode(rule.advice))
            ));
            plan.notes.forEach(note => box.append($('<p class="text-xs text-warning mb-0">').text(note)));
            box.append($('<p class="text-xs text-secondary mb-0 mt-1">').text(@json(__('Sugerencia del sistema experto; puede modificar los valores.'))));
            return box;
        }

        function copy(){
            $('#indice-insulina').val($('#input-indice-insulina').val());
            $('#talla').val($('#input-size').val());
            $('#peso').val($('#input-weight').val());
            $('#actividad_fisica').val($('#input-physical-activity').val());
            $('#edad').val($('#input-age').val());
            $('#imc').val($('#input-imc').val());
        }
    </script>
@endsection
