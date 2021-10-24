<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog  modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Crear Diagnóstico</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-name">{{ __('Carbohidrato') }}</label>
                <input type="text" name="first_name" id="input-carbohidrato" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Indice de Insulina') }}" value="{{ old('first_name') }}" required autofocus>
            </div>
            <div class="form-group{{ $errors->has('weight') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-weight">{{ __('Isocalorico') }}</label>
                <input type="text" name="weight" id="input-isocalorico" class="form-control{{ $errors->has('weight') ? ' is-invalid' : '' }}" placeholder="{{ __('Peso') }}" value="{{ old('weight') }}" required autofocus onchange="calcularIMC()">
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-size">{{ __('Lipido') }}</label>
                <input type="text" name="size" id="input-lipido" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-size">{{ __('Proteina') }}</label>
                <input type="text" name="size" id="input-proteina" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-4">
                <label class="form-control-label" for="input-size">{{ __('IMC DESEADO') }}</label>
                <input type="text" name="size" id="input-imc-deseado" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-4">
                <label class="form-control-label" for="input-size">{{ __('Resultado Método Harray B.') }}</label>
                <input type="text" name="size" id="input-result-harry" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-4">
                <label class="form-control-label" for="input-size">{{ __('Resultado Método Pulgar') }}</label>
                <input type="text" name="size" id="input-result-pulgar" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
            </div>

            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-4">
                <input type="text" name="size" id="indice-insulina">
                <input type="text" name="size" id="talla">
                <input type="text" name="size" id="peso">
                <input type="text" name="size" id="actividad_fisica">
                <input type="text" name="size" id="horario_comida">
                <input type="text" name="size" id="jornada_laboral">
                <input type="text" name="size" id="horario_actividad">
                <input type="text" name="size" id="edad">
                <input type="text" name="size" id="ingesta_calorica">
                <input type="text" name="size" id="imc">
                <input type="text" name="size" value="{{ $patient->id }}">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>