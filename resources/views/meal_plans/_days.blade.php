{{-- The days of a plan (MealPlan of expert/app/responses.py): summary against the targets and meals. Tabs with several days. --}}
@php
    $nutrients = ['energy' => ['Energía', 'kcal'], 'carbohydrates' => ['Carbohidratos', 'g'], 'proteins' => ['Proteínas', 'g'], 'fats' => ['Grasas', 'g']];
    $tolerance = config('clinical.meal_plan.tolerance_percent');
    $number = fn ($v) => rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',');
    $portions = fn ($p) => $number($p).' '.($p == 1 ? __('porción') : __('porciones'));
    $several = count($plan['days']) > 1;
@endphp

@if ($several)
    <ul class="nav nav-pills mb-3" role="tablist" data-day-tabs="{{ $tabsId }}">
        @foreach ($plan['days'] as $day)
            <li class="nav-item">
                <button type="button" @class(['nav-link', 'active' => $loop->first]) data-day="{{ $loop->index }}" role="tab">{{ __('Día :n', ['n' => $day['day']]) }}</button>
            </li>
        @endforeach
    </ul>
@endif

@foreach ($plan['days'] as $day)
    <div data-day-panel="{{ $tabsId }}" @if (! $loop->first) hidden @endif>
        <div class="row g-3 mb-3">
            @foreach ($nutrients as $key => [$label, $unit])
                @php($deviation = $day['deviation_percent'][$key])
                <div class="col-6 col-md-3">
                    <div class="border border-radius-lg p-2 h-100">
                        <p class="text-xs text-uppercase text-secondary font-weight-bolder mb-0">{{ $label }}</p>
                        <span class="text-sm font-weight-bold">{{ $number($day['totals'][$key]) }} {{ $unit }}</span>
                        <span class="text-xs text-secondary">/ {{ $number($plan['targets'][$key]) }}</span>
                        <span @class(['text-xs', 'text-success' => abs($deviation) <= $tolerance[$key] + 0.3, 'text-warning' => abs($deviation) > $tolerance[$key] + 0.3])>
                            ({{ $deviation > 0 ? '+' : '' }}{{ $number($deviation) }} %)
                        </span>
                    </div>
                </div>
            @endforeach
            @foreach (['glycemic_load' => ['Carga glucémica', ''], 'saturated_fat' => ['Grasa saturada', 'g']] as $key => [$label, $unit])
                @isset($plan['limits'][$key], $day['totals'][$key])
                    @php($within = $day['totals'][$key] <= $plan['limits'][$key] * 1.02)
                    <div class="col-6 col-md-3">
                        <div class="border border-radius-lg p-2 h-100">
                            <p class="text-xs text-uppercase text-secondary font-weight-bolder mb-0">{{ $label }}</p>
                            <span class="text-sm font-weight-bold">{{ $number($day['totals'][$key]) }} {{ $unit }}</span>
                            <span @class(['text-xs', 'text-success' => $within, 'text-warning' => ! $within])>/ {{ __('máx.') }} {{ $number($plan['limits'][$key]) }}</span>
                        </div>
                    </div>
                @endisset
            @endforeach
        </div>

        <div class="row g-3">
            @foreach ($day['meals'] as $meal)
                <div class="col-md-6 col-xl-4">
                    <div class="border border-radius-lg p-3 h-100">
                        <div class="d-flex align-items-baseline mb-1">
                            <strong class="text-sm">{{ $meal['label'] }}</strong>
                            <span class="text-xs text-secondary ms-auto">{{ $number($meal['totals']['energy']) }} / {{ $meal['energy_target'] }} kcal</span>
                        </div>
                        @if (empty($meal['items']))
                            <p class="text-xs text-secondary mb-0">{{ __('Sin alimentos.') }}</p>
                        @endif
                        <ul class="list-unstyled mb-0 text-sm">
                            @foreach ($meal['items'] as $item)
                                <li>
                                    {{ $item['name'] }}:
                                    <strong>{{ $item['grams'] ? $item['grams'].' g' : $portions($item['portions']) }}</strong>
                                    <span class="text-xs text-secondary">({{ $portions($item['portions']) }} · {{ $item['group'] }})</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>

        @foreach ($day['notes'] ?? [] as $note)
            <p class="text-xs text-warning mb-0 mt-2">{{ $note }}</p>
        @endforeach
    </div>
@endforeach

@foreach ($plan['notes'] ?? [] as $note)
    <p class="text-xs text-warning mb-0 mt-2">{{ $note }}</p>
@endforeach

@if ($several)
    @once
        <script>
            // Day tabs without depending on the Bootstrap JS version of the template.
            document.addEventListener('click', function (event) {
                const button = event.target.closest('[data-day-tabs] [data-day]');
                if (!button) return;
                const id = button.closest('[data-day-tabs]').dataset.dayTabs;
                button.closest('[data-day-tabs]').querySelectorAll('[data-day]').forEach(b => b.classList.toggle('active', b === button));
                document.querySelectorAll(`[data-day-panel="${id}"]`).forEach((panel, i) => panel.hidden = i !== Number(button.dataset.day));
            });
        </script>
    @endonce
@endif
