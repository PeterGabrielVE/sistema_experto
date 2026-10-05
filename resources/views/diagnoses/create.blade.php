<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog  modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Crear Consulta</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="{{ route('diagnosis.store') }}" autocomplete="off" enctype="multipart/form-data">
      @csrf
      <div class="modal-body">
        {{-- Filled by the expert service (diagnoses/index.blade.php, diagnosticar()); the fields stay editable. --}}
        <div id="macro-plan-summary" class="mb-3"></div>
        <div class="row">
            <div class="form-group col-3">
              <label class="form-control-label" for="input-carbohidrato">{{ __('Carbohidratos/Día') }}</label>
              <div class="input-group">
                <input type="text" name="carbohydrate" id="input-carbohidrato" class="form-control">
                <span class="input-group-text">gr</span>
              </div>
            </div>
            <div class="form-group col-3">
                <label class="form-control-label" for="input-isocalorico">{{ __('Isoglucídico (por comida)') }}</label>
                <div class="input-group">
                  <input type="text" name="isocaloric_carbohydrate" id="input-isocalorico" class="form-control">
                  <span class="input-group-text">gr</span>
                </div>
            </div>
            <div class="form-group col-3">
                <label class="form-control-label" for="input-lipido">{{ __('Lipidos/Día') }}</label>
                <div class="input-group">
                  <input type="text" name="lipido" id="input-lipido" class="form-control">
                  <span class="input-group-text">gr</span>
                </div>
            </div>
            <div class="form-group col-3">
                <label class="form-control-label" for="input-isocalorico2">{{ __('Isocalorico Lípido (por comida)') }}</label>
                <div class="input-group">
                  <input type="text" name="isocaloric_lipido" id="input-isocalorico2" class="form-control">
                  <span class="input-group-text">gr</span>
                </div>
            </div>
            <div class="form-group col-3">
                <label class="form-control-label" for="input-proteina">{{ __('Proteinas/Día') }}</label>
                <div class="input-group">
                  <input type="text" name="protein" id="input-proteina" class="form-control">
                  <span class="input-group-text">gr</span>
                </div>
            </div>
            <div class="form-group col-3">
                <label class="form-control-label" for="input-isocalorico3">{{ __('Isoproteico (por comida)') }}</label>
                <div class="input-group">
                  <input type="text" name="isocaloric_protein" id="input-isocalorico3" class="form-control">
                  <span class="input-group-text">gr</span>
                </div>
            </div>
            <div class="form-group col-3">
                <label class="form-control-label" for="input-result-pulgar">{{ __('Requerimiento energético') }}</label>
                <div class="input-group">
                  <input type="text" name="result_pulgar" id="input-result-pulgar" class="form-control">
                  <span class="input-group-text">kcal/día</span>
                </div>
            </div>
            <div class="form-group col-3">
                <label class="form-control-label" for="input-imc-deseado">{{ __('Factor de Corrección') }}</label>
                <div class="input-group">
                  <input type="text" name="imc_desired" id="input-imc-deseado" class="form-control">
                  <span class="input-group-text">kcal/kg</span>
                </div>
            </div>

            <input type="hidden" name="insulin_index" id="indice-insulina">
            <input type="hidden" name="size" id="talla">
            <input type="hidden" name="weight" id="peso">
            <input type="hidden" name="physical_activity" id="actividad_fisica">
            <input type="hidden" name="age" id="edad">
            <input type="hidden" name="imc" id="imc">
            <input type="hidden" name="id_patient" value="{{ $patient->id }}">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary">Generar Reporte</button>
      </div>
      </form>
    </div>
  </div>
</div>
