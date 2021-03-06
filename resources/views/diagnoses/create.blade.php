<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog  modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Crear Consulta</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="{{ route('diagnosis.store') }}" autocomplete="off" enctype="multipart/form-data">
      @csrf
      <div class="modal-body">
        <div class="row">
            <div class="form-group{{ $errors->has('first_name') ? ' has-danger' : '' }} col-3">
              <label class="form-control-label" for="input-name">{{ __('Carbohidratos/Día') }}</label>
              <div class="ui right labeled input">
              <input type="text" name="carbohydrate" id="input-carbohidrato" class="form-control{{ $errors->has('first_name') ? ' is-invalid' : '' }}" placeholder="{{ __('Indice de Insulina') }}" value="{{ old('first_name') }}" required autofocus>
                <div class="ui basic label">gr</div>
              </div>
            </div>
            <div class="form-group{{ $errors->has('weight') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-weight">{{ __('Isoglucídico') }}</label>
                <div class="ui right labeled input">
                  <input type="text" name="isocaloric_carbohydrate" id="input-isocalorico" class="form-control{{ $errors->has('weight') ? ' is-invalid' : '' }}" placeholder="{{ __('Peso') }}" value="{{ old('weight') }}" required autofocus onchange="calcularIMC()">
                  <div class="ui basic label">gr</div>
                </div>
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-size">{{ __('Lipidos/Día') }}</label><br>
                <div class="ui right labeled input">
                  <input type="text" name="lipido" id="input-lipido" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
                  <div class="ui basic label">gr</div>
                </div>
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-size">{{ __('Isocalorico Lípido') }}</label>
                <div class="ui right labeled input">
                <input type="text" name="isocaloric_lipido" id="input-isocalorico2" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
                  <div class="ui basic label">gr</div>
                </div>
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-size">{{ __('Proteinas/Día') }}</label><br>
                <div class="ui right labeled input">
                <input type="text" name="protein" id="input-proteina" class="form-control{{ $errors->has('protein') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
                  <div class="ui basic label">gr</div>
                </div>
            
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-size">{{ __('Isoproteico') }}</label>
                <div class="ui right labeled input">
                <input type="text" name="isocaloric_protein" id="input-isocalorico3" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
                  <div class="ui basic label">gr</div>
                </div>
            </div>
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-3">
                <label class="form-control-label" for="input-size">{{ __('Factor de Corrección') }}</label>
                <div class="ui right labeled input">
                <input type="text" name="imc_desired" id="input-imc-deseado" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
                  <div class="ui basic label">kg/m</div>
                </div>
               
            </div>

            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-2">
                <label class="form-control-label" for="input-size">{{ __('Resultado Método Pulgar') }}</label>
                <input type="text" name="result_pulgar" id="input-result-pulgar" class="form-control{{ $errors->has('size') ? ' is-invalid' : '' }}" placeholder="{{ __('Talla') }}" value="{{ old('address') }}" required autofocus onchange="calcularIMC()">
            </div>
      
            <div class="form-group{{ $errors->has('size') ? ' has-danger' : '' }} col-4">
                <input type="hidden" name="insulin_index" id="indice-insulina">
                <input type="hidden" name="size" id="talla">
                <input type="hidden" name="weight" id="peso">
                <input type="hidden" name="physical_activity" id="actividad_fisica">
                <input type="hidden" name="age" id="edad">
                <input type="hidden" name="imc" id="imc">
                <input type="hidden" name="id_patient" value="{{ $patient->id }}">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary">Generar Reporte</button>
      </div>
      </form>
    </div>
  </div>
</div>
