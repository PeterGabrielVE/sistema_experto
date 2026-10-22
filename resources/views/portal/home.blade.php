{{-- Home of the patient portal (US-6.3, D-3). Only the foods and portions of the saved menu:
     no targets, energy, totals nor notes of the generator, which are clinical. --}}
@extends('layouts.app', [
    'namePage' => 'Portal de pacientes',
    'activePage' => 'portal',
])

@php
    $portions = function (float $portions, $grams) {
        $amount = $portions == 0.5 ? __('½ porción') : trans_choice(':n porción|:n porciones', $portions == 1 ? 1 : 2, [
            'n' => rtrim(rtrim(number_format($portions, 1, ',', ''), '0'), ','),
        ]);

        return $grams ? $amount.' ('.rtrim(rtrim(number_format((float) $grams, 1, ',', '.'), '0'), ',').' g)' : $amount;
    };
@endphp

@section('content')
    <h4 class="mb-3">{{ __('Hola :name', ['name' => $patient->first_name]) }}</h4>

    <div class="card mb-4">
        <div class="card-body">
            <h6>{{ __('Próxima consulta') }}</h6>
            @if ($appointment)
                <p class="mb-1"><strong>{{ $appointment->localScheduledAt()->format('d/m/Y H:i') }}</strong></p>
                @if ($appointment->doctor)
                    <p class="text-sm mb-1">{{ __('Profesional: :name', ['name' => $appointment->doctor->name]) }}</p>
                @endif
                @if ($appointment->notes)
                    <p class="text-sm mb-0">{{ __('Indicaciones:') }} {{ $appointment->notes }}</p>
                @endif
            @else
                <p class="text-sm text-muted mb-0">{{ __('No tiene una próxima consulta programada.') }}</p>
            @endif
        </div>
    </div>

    @if ($mealPlan && ! empty($mealPlan->plan['days']))
        @foreach ($days as $day)
            @include('portal._day', $day)
        @endforeach
    @endif

    <div class="card">
        <div class="card-body">
            <h6>{{ __('Su plan alimentario') }}</h6>
            @if (! $mealPlan || empty($mealPlan->plan['days']))
                <p class="text-sm text-muted mb-0">{{ __('Su profesional aún no le ha guardado un plan alimentario.') }}</p>
            @else
                <p class="text-xs text-secondary">
                    {{ __('guardado el :date', ['date' => $mealPlan->updated_at->copy()->setTimezone(config('app.clinic_timezone'))->format('d/m/Y')]) }}
                    · {{ __('porciones de intercambio') }}
                </p>
                @foreach ($mealPlan->plan['days'] as $day)
                    <h6 class="text-sm mt-3">{{ __('Día :n', ['n' => $day['day']]) }}</h6>
                    <div class="row">
                        @foreach ($day['meals'] as $meal)
                            @continue(empty($meal['items']))
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <p class="text-sm font-weight-bold mb-1">{{ $meal['label'] }}</p>
                                <ul class="text-sm ps-3 mb-0">
                                    @foreach ($meal['items'] as $item)
                                        <li>{{ $item['name'] }}: {{ $portions((float) $item['portions'], $item['grams'] ?? null) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
