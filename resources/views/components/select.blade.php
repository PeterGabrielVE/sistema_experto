@props(['name', 'options' => [], 'selected' => null])

@php
    $current = old($name, $selected);
@endphp

<select name="{{ $name }}" {{ $attributes }}>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected($current !== null && (string) $current === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
