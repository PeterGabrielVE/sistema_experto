{{-- Menu of one day the patient can record (US-6.2): today or yesterday, a choice per meal.
     "Sin marcar" saves nothing: it is no data, never "no cumplí". --}}
@php
    $choices = ['done' => __('Cumplí'), 'partial' => __('En parte'), 'missed' => __('No cumplí'), '' => __('Sin marcar')];
@endphp
<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-0">{{ $title }}</h6>
        <p class="text-xs text-secondary mb-2">
            {{ $date->locale('es')->translatedFormat('l d/m') }}
            @if ($menu)
                · {{ __('Día :n del plan', ['n' => $menu['day']]) }}
            @endif
        </p>
        @if (! $menu)
            <p class="text-sm text-muted mb-0">{{ __('Su plan aún no comenzaba ese día.') }}</p>
        @else
            <form method="post" action="{{ route('portal.adherence.update', $date->toDateString()) }}">
                @csrf
                @method('put')
                @foreach ($menu['meals'] as $meal)
                    <fieldset class="mb-3">
                        <legend class="text-sm font-weight-bold mb-0">{{ $meal['label'] }}</legend>
                        <p class="text-sm mb-1">
                            {{ collect($meal['items'])->map(fn ($item) => $item['name'].' ('.$portions((float) $item['portions'], $item['grams'] ?? null).')')->implode(', ') }}
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($choices as $value => $label)
                                @php($id = 'meal-'.$date->toDateString().'-'.$meal['key'].'-'.($value ?: 'none'))
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="meals[{{ $meal['key'] }}]" value="{{ $value }}" @checked(($marks[$meal['key']] ?? '') === $value) id="{{ $id }}">
                                    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
                @error('meals')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
                <button type="submit" class="btn btn-primary btn-sm mb-0">{{ __('Guardar') }}</button>
            </form>
        @endif
    </div>
</div>
