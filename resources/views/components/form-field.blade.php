@props(['name', 'label', 'type' => 'text', 'value' => null, 'unit' => null, 'col' => 'col-md-4'])

<div class="form-group {{ $col }}{{ $errors->has($name) ? ' has-danger' : '' }}">
    <label class="form-control-label" for="input-{{ $name }}">
        {{ $label }}@if($unit) <small class="text-muted">({{ $unit }})</small>@endif
    </label>
    <input type="{{ $type }}" name="{{ $name }}" id="input-{{ $name }}" value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>
    @include('alerts.feedback', ['field' => $name])
</div>
