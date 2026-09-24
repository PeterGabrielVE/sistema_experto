@props(['name', 'label', 'value' => null, 'col' => 'col-md-6', 'rows' => 2])

<div class="form-group {{ $col }}{{ $errors->has($name) ? ' has-danger' : '' }}">
    <label class="form-control-label" for="input-{{ $name }}">{{ $label }}</label>
    <textarea name="{{ $name }}" id="input-{{ $name }}" rows="{{ $rows }}" maxlength="2000"
        {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>{{ old($name, $value) }}</textarea>
    @include('alerts.feedback', ['field' => $name])
</div>
