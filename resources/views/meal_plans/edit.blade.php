@extends('layouts.app', [
    'class' => 'sidebar-mini ',
    'namePage' => 'Propuesta de menú',
    'activePage' => 'patient',
    'activeNav' => '',
])

@php
    // Editor state: foods and portions per meal and day (amounts are recomputed when saving).
    $initial = $plan
        ? collect($plan['days'])->map(fn ($day) => collect($meals)->mapWithKeys(fn ($meal) => [
            $meal['key'] => collect(collect($day['meals'])->firstWhere('key', $meal['key'])['items'] ?? [])
                ->map(fn ($item) => ['food_id' => $item['food_id'], 'portions' => $item['portions']])->values(),
        ]))->values()
        : collect(range(1, $days))->map(fn () => collect($meals)->mapWithKeys(fn ($meal) => [$meal['key'] => []]));
@endphp

@section('content')
    <div class="panel-header panel-header-sm"></div>
    <div class="content">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h3 class="mb-0">{{ __('Propuesta de menú') }}</h3>
                    <a href="{{ route('result', $diagnosis) }}#meal-plan" class="btn btn-primary btn-round ms-auto mb-0">{{ __('Volver al resultado') }}</a>
                </div>
                <p class="text-sm text-secondary mb-0 mt-2">
                    {{ __('Metas de la consulta') }}:
                    <strong>{{ number_format($targets['energy'], 0, ',', '.') }} kcal</strong> ·
                    {{ __('carbohidratos') }} {{ $targets['carbohydrates'] + 0 }} g ·
                    {{ __('proteínas') }} {{ $targets['proteins'] + 0 }} g ·
                    {{ __('grasas') }} {{ $targets['fats'] + 0 }} g ·
                    {{ __('carga glucémica máx.') }} {{ $limits['glycemic_load'] + 0 }} ·
                    {{ __('grasa saturada máx.') }} {{ $limits['saturated_fat'] + 0 }} g
                </p>
                @if (! $plan)
                    <p class="text-sm text-warning mb-0">{{ __('El sistema experto no generó una propuesta: arme el menú a mano o intente generarla de nuevo.') }}</p>
                @endif
                @foreach ($plan['notes'] ?? [] as $note)
                    <p class="text-xs text-warning mb-0">{{ $note }}</p>
                @endforeach
            </div>

            <div class="card-body pt-0">
                @include('alerts.errors')

                <form method="get" action="{{ route('meal-plan.edit', $diagnosis) }}" class="d-flex flex-wrap align-items-end gap-2 mb-3" id="generate-form">
                    <input type="hidden" name="nueva" value="1">
                    <input type="hidden" name="variante" value="{{ $variant + 1 }}">
                    <div>
                        <label class="form-control-label text-xs" for="input-days">{{ __('Días') }}</label>
                        <select name="dias" id="input-days" class="form-control form-control-sm">
                            @foreach (range(1, config('clinical.meal_plan.max_days')) as $n)
                                <option value="{{ $n }}" @selected($n === $days)>{{ trans_choice(':n día|:n días', $n, ['n' => $n]) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary mb-0">{{ __('Generar nueva propuesta') }}</button>
                    <span class="text-xs text-secondary">{{ __('Reemplaza lo que esté en el editor; lo guardado no cambia hasta que guarde.') }}</span>
                </form>

                <div id="menu-editor"></div>

                <form method="post" action="{{ route('meal-plan.update', $diagnosis) }}" id="save-form" class="text-center mt-4">
                    @csrf
                    @method('put')
                    <input type="hidden" name="plan" id="input-plan">
                    <input type="hidden" name="generated" id="input-generated" value="{{ $generated ? 1 : 0 }}">
                    <button type="submit" class="btn btn-info">{{ __('Guardar propuesta') }}</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const CATALOG = @json($catalog);
            const FOODS = Object.fromEntries(CATALOG.map(f => [f.id, f]));
            const MEALS = @json(collect($meals)->map(fn ($m) => ['key' => $m['key'], 'label' => $m['label'], 'share' => $m['energy_share']])->values());
            const TARGETS = @json($targets);
            const LIMITS = @json($limits);
            const TOLERANCE = @json(config('clinical.meal_plan.tolerance_percent'));
            const MAX_DAYS = {{ (int) config('clinical.meal_plan.max_days') }};
            const NUTRIENTS = [['energy', 'Energía', 'kcal'], ['carbohydrates', 'Carbohidratos', 'g'], ['proteins', 'Proteínas', 'g'], ['fats', 'Grasas', 'g']];
            const CEILINGS = [['glycemic_load', 'Carga glucémica', ''], ['saturated_fat', 'Grasa saturada', 'g']];

            let state = @json($initial);
            let current = 0;
            let dirty = false;
            const root = document.getElementById('menu-editor');
            const fmt = v => (Math.round(v * 10) / 10).toLocaleString('es-CL');

            function el(tag, attrs = {}, ...children) {
                const node = document.createElement(tag);
                Object.entries(attrs).forEach(([k, v]) => k === 'class' ? node.className = v : k.startsWith('on') ? node.addEventListener(k.slice(2), v) : node.setAttribute(k, v));
                children.flat().forEach(c => node.append(c instanceof Node ? c : document.createTextNode(c)));
                return node;
            }

            function changed() {
                dirty = true;
                document.getElementById('input-generated').value = 0;
                render();
            }

            function totalsOf(items) {
                const sum = Object.fromEntries([...NUTRIENTS, ...CEILINGS].map(([k]) => [k, 0]));
                items.forEach(item => {
                    const food = FOODS[item.food_id];
                    if (food) Object.keys(sum).forEach(k => sum[k] += food[k] * item.portions);
                });
                return sum;
            }

            function foodSelect(item) {
                const select = el('select', { class: 'form-control form-control-sm', 'aria-label': 'Alimento', onchange: e => { item.food_id = Number(e.target.value); changed(); } });
                if (!FOODS[item.food_id]) select.append(el('option', { value: '' }, 'Seleccione...'));
                [...new Set(CATALOG.map(f => f.group))].forEach(group => {
                    const optgroup = el('optgroup', { label: group });
                    CATALOG.filter(f => f.group === group).forEach(f => {
                        const option = el('option', { value: f.id }, f.name);
                        if (f.id === item.food_id) option.selected = true;
                        optgroup.append(option);
                    });
                    select.append(optgroup);
                });
                return select;
            }

            function mealCard(day, meal) {
                const items = day[meal.key];
                const totals = totalsOf(items);
                const target = Math.round(TARGETS.energy * meal.share / 100);
                const rows = items.map((item, i) => {
                    const food = FOODS[item.food_id];
                    return el('tr', {},
                        el('td', {}, foodSelect(item)),
                        el('td', { style: 'width: 90px' }, el('input', {
                            type: 'number', step: '0.5', min: '0.5', max: '10', value: item.portions, class: 'form-control form-control-sm', 'aria-label': 'Porciones',
                            onchange: e => { item.portions = Math.max(0.5, Math.round(Number(e.target.value) * 2) / 2 || 0.5); changed(); },
                        })),
                        el('td', { class: 'text-xs text-secondary', style: 'width: 70px' }, food && food.grams ? Math.round(food.grams * item.portions) + ' g' : ''),
                        el('td', { class: 'text-xs text-secondary', style: 'width: 70px' }, food ? fmt(food.energy * item.portions) + ' kcal' : ''),
                        el('td', { style: 'width: 40px' }, el('button', { type: 'button', class: 'btn btn-link text-danger btn-sm mb-0 px-1', title: 'Quitar', 'aria-label': 'Quitar', onclick: () => { items.splice(i, 1); changed(); } }, '×')),
                    );
                });
                const off = Math.abs(totals.energy - target) > target * TOLERANCE.meal_energy / 100;
                return el('div', { class: 'col-lg-6' }, el('div', { class: 'border border-radius-lg p-3 h-100' },
                    el('div', { class: 'd-flex align-items-baseline mb-2' },
                        el('strong', { class: 'text-sm' }, meal.label),
                        el('span', { class: 'text-xs ms-auto ' + (off ? 'text-warning' : 'text-secondary') }, `${fmt(totals.energy)} / ${target} kcal`)),
                    el('table', { class: 'table table-sm mb-1' }, el('tbody', {}, rows)),
                    el('button', { type: 'button', class: 'btn btn-sm btn-outline-secondary mb-0', onclick: () => { items.push({ food_id: null, portions: 1 }); changed(); } }, '+ Agregar alimento'),
                ));
            }

            function summary(day) {
                const totals = totalsOf(MEALS.flatMap(m => day[m.key]));
                const tile = (label, value, extra, ok) => el('div', { class: 'col-6 col-md-2' }, el('div', { class: 'border border-radius-lg p-2 h-100' },
                    el('p', { class: 'text-xs text-uppercase text-secondary font-weight-bolder mb-0' }, label),
                    el('span', { class: 'text-sm font-weight-bold' }, value), ' ',
                    el('span', { class: 'text-xs ' + (ok ? 'text-success' : 'text-warning') }, extra)));
                return el('div', { class: 'row g-2 mb-3', 'data-summary': '' },
                    NUTRIENTS.map(([k, label, unit]) => {
                        const deviation = (totals[k] - TARGETS[k]) / TARGETS[k] * 100;
                        return tile(label, `${fmt(totals[k])} ${unit}`, `/ ${fmt(TARGETS[k])} (${deviation > 0 ? '+' : ''}${fmt(deviation)} %)`, Math.abs(deviation) <= TOLERANCE[k] + 0.3);
                    }),
                    CEILINGS.map(([k, label, unit]) => tile(label, `${fmt(totals[k])} ${unit}`, `/ máx. ${fmt(LIMITS[k])}`, totals[k] <= LIMITS[k] * 1.02)));
            }

            function render() {
                current = Math.min(current, state.length - 1);
                const day = state[current];
                const tabs = el('ul', { class: 'nav nav-pills mb-3' }, state.map((_, i) => el('li', { class: 'nav-item' },
                    el('button', { type: 'button', class: 'nav-link' + (i === current ? ' active' : ''), onclick: () => { current = i; render(); } }, `Día ${i + 1}`))));
                const actions = el('div', { class: 'd-flex gap-2 mb-3' },
                    el('button', { type: 'button', class: 'btn btn-sm btn-outline-secondary mb-0', onclick: () => {
                        if (state.length >= MAX_DAYS) return;
                        state.push(JSON.parse(JSON.stringify(day))); current = state.length - 1; changed();
                    } }, '+ Copiar este día'),
                    state.length > 1 ? el('button', { type: 'button', class: 'btn btn-sm btn-outline-danger mb-0', onclick: () => { state.splice(current, 1); changed(); } }, 'Quitar este día') : '');
                root.replaceChildren(tabs, actions, summary(day), el('div', { class: 'row g-3' }, MEALS.map(meal => mealCard(day, meal))));
            }

            document.getElementById('save-form').addEventListener('submit', function () {
                document.getElementById('input-plan').value = JSON.stringify({
                    days: state.map(day => ({ meals: MEALS.map(m => ({ key: m.key, items: day[m.key].filter(i => FOODS[i.food_id]) })) })),
                });
                dirty = false;
            });
            document.getElementById('generate-form').addEventListener('submit', function (event) {
                if (dirty && !confirm('Hay cambios sin guardar en el editor. ¿Generar una nueva propuesta igualmente?')) event.preventDefault();
            });
            window.addEventListener('beforeunload', function (event) { if (dirty) event.preventDefault(); });

            render();
        })();
    </script>
@endsection
