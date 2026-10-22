{{-- Menu of one day the patient can record (US-6.2): today or yesterday. --}}
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
            @foreach ($menu['meals'] as $meal)
                <div class="mb-2">
                    <p class="text-sm font-weight-bold mb-0">{{ $meal['label'] }}</p>
                    <p class="text-sm mb-0">
                        {{ collect($meal['items'])->map(fn ($item) => $item['name'].' ('.$portions((float) $item['portions'], $item['grams'] ?? null).')')->implode(', ') }}
                    </p>
                </div>
            @endforeach
        @endif
    </div>
</div>
