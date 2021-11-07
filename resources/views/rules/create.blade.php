<!-- The Modal -->
<div class="modal" id="myModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Agregar Recomendación</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <form method="post" action="{{ route('recommendation.store') }}" autocomplete="off"
                            enctype="multipart/form-data">
                            @csrf

                            <h6 class="heading-small text-muted mb-4">{{ __('Descripción de la recomendación') }}</h6>
                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="form-group{{ $errors->has('description') ? ' has-danger' : '' }} col-12">
                                        <label class="form-control-label" for="input-description">{{ __('Clasificación') }}</label>
                                        <input type="text" name="description" id="input-comment" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" placeholder="{{ __('Descripción') }}" value="{{ old('') }}" required autofocus>

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

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>