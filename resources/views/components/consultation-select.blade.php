@props(['consultations' => [], 'selected' => null, 'col' => 'col-md-4'])

{{-- Optional link of a measurement or lab result to one of the patient's consultations. --}}
<div class="form-group {{ $col }}{{ $errors->has('diagnosis_id') ? ' has-danger' : '' }}">
    <label class="form-control-label" for="input-diagnosis_id">{{ __('Consulta asociada') }}</label>
    <x-select name="diagnosis_id" :options="['' => __('Sin consulta')] + $consultations" :selected="$selected"
        id="input-diagnosis_id" class="form-control{{ $errors->has('diagnosis_id') ? ' is-invalid' : '' }}" />
    @include('alerts.feedback', ['field' => 'diagnosis_id'])
</div>
