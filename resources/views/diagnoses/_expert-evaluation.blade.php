{{-- Evaluation of the expert diagnosis service (ExpertDiagnosisService), medical team only. --}}
@php
    $result = $expert['result'];
    $labels = \App\Services\ExpertDiagnosisService::STATUS_LABELS;
    $tone = [
        'normal' => 'success', 'no_sugerida' => 'success', 'ausente' => 'success',
        'prediabetes' => 'warning', 'posible' => 'warning', 'limitrofe' => 'warning',
        'rango_diabetes' => 'danger', 'diabetes_conocida' => 'danger', 'probable' => 'danger', 'presente' => 'danger', 'alterado' => 'danger',
        'indeterminado' => 'secondary',
    ];
    $severity = [
        'alert' => ['danger', __('Alerta')],
        'warning' => ['warning', __('Atención')],
        'info' => ['info', __('Info')],
    ];
    $a = $result['assessments'];
    $details = [
        'glycemic_status' => null,
        'insulin_resistance' => $a['insulin_resistance']['evaluated']
            ? __(':positive de :evaluated indicadores alterados', ['positive' => $a['insulin_resistance']['positive'], 'evaluated' => $a['insulin_resistance']['evaluated']])
            : null,
        'metabolic_syndrome' => __(':met de 5 criterios', ['met' => $a['metabolic_syndrome']['met']])
            .($a['metabolic_syndrome']['unknown'] ? ' · '.__(':n sin datos', ['n' => $a['metabolic_syndrome']['unknown']]) : ''),
        'atherogenic_profile' => ($a['atherogenic_profile']['evaluated'] ?? 0)
            ? __(':positive de :evaluated índices alterados', ['positive' => $a['atherogenic_profile']['positive'], 'evaluated' => $a['atherogenic_profile']['evaluated']])
            : null,
    ];
    $titles = [
        'glycemic_status' => __('Estado glicémico'),
        'insulin_resistance' => __('Resistencia a la insulina'),
        'metabolic_syndrome' => __('Síndrome metabólico'),
        'atherogenic_profile' => __('Perfil aterogénico'),
    ];
    $measurement = $expert['sources']['measurement'];
    $labResult = $expert['sources']['labResult'];
    $number = fn ($v) => is_numeric($v) ? rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',') : $v;
@endphp

<div class="mt-4" id="expert-evaluation">
    <div class="d-flex flex-wrap align-items-baseline gap-2 mb-1">
        <h6 class="heading-small text-muted mb-0">{{ __('Evaluación del sistema experto') }}</h6>
        <span class="text-xs text-secondary ms-auto">{{ __('Reglas v:version', ['version' => $result['ruleset_version']]) }}</span>
    </div>
    <p class="text-xs text-secondary mb-3">
        {{ __('Datos usados:') }}
        {{ $measurement ? __('medición del :date', ['date' => $measurement->measured_at->format('d/m/Y')]) : __('peso y talla de la consulta') }}
        ·
        {{ $labResult ? __('examen del :date', ['date' => $labResult->taken_at->format('d/m/Y')]) : __('sin examen de laboratorio') }}
        · {{ __('ficha clínica') }}
    </p>

    <div class="row g-3 mb-3">
        {{-- Results of an older ruleset may lack an assessment. --}}
        @foreach (array_intersect_key($titles, $a) as $key => $title)
            @php($status = $a[$key]['status'])
            <div class="col-md-6 col-xl-3">
                <div class="border border-radius-lg p-3 h-100">
                    <p class="text-xs text-uppercase text-secondary font-weight-bolder mb-1">{{ $title }}</p>
                    <span class="badge bg-gradient-{{ $tone[$status] ?? 'secondary' }}">{{ $labels[$key][$status] ?? $status }}</span>
                    @if ($details[$key])
                        <p class="text-xs text-secondary mb-0 mt-1">{{ $details[$key] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if (empty($result['findings']))
        <p class="text-sm text-success">{{ __('Sin hallazgos con los datos disponibles.') }}</p>
    @else
        <ul class="list-group mb-3">
            @foreach ($result['findings'] as $finding)
                @php([$color, $label] = $severity[$finding['severity']] ?? ['secondary', $finding['severity']])
                <li class="list-group-item border-0 border-start border-3 border-{{ $color }} mb-2 bg-gray-100 border-radius-lg">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge badge-sm bg-gradient-{{ $color }}">{{ $label }}</span>
                        <strong class="text-sm">{{ $finding['title'] }}</strong>
                        <span class="text-xs text-secondary ms-auto">{{ $finding['rule_id'] }}</span>
                    </div>
                    @if (! empty($finding['evidence']))
                        <p class="text-xs text-secondary mb-0 mt-1">{{ implode(' · ', $finding['evidence']) }}</p>
                    @endif
                    @if ($finding['recommendation'])
                        <p class="text-sm mb-0 mt-1">{{ $finding['recommendation'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Results of an older ruleset have no macronutrients. --}}
    @php($plan = $result['macronutrients'] ?? null)
    @if ($plan)
        <div class="border border-radius-lg p-3 mb-3" id="macro-plan">
            <p class="text-xs text-uppercase text-secondary font-weight-bolder mb-2">{{ __('Distribución de macronutrientes sugerida') }}</p>
            @if ($plan['status'] !== 'calculado')
                <p class="text-sm text-secondary mb-0">{{ $plan['reason'] }}</p>
            @else
                @php($energy = $plan['energy'])
                <p class="text-sm mb-2">
                    <strong>{{ number_format($energy['target'], 0, ',', '.') }} kcal/día</strong>
                    <span class="text-xs text-secondary">
                        · {{ __('gasto basal :bmr kcal × :factor (actividad :level)', ['bmr' => number_format($energy['bmr'], 0, ',', '.'), 'factor' => $number($energy['activity_factor']), 'level' => mb_strtolower($energy['activity_level'])]) }}
                        @if ($energy['adjustment'])
                            · {{ $energy['adjustment'] > 0 ? __('superávit') : __('déficit') }} {{ abs($energy['adjustment']) }} kcal
                        @endif
                    </span>
                </p>
                <div class="table-responsive">
                    <table class="table table-sm mb-2">
                        <thead>
                            <tr><th>{{ __('Macronutriente') }}</th><th>%</th><th>{{ __('Gramos/día') }}</th><th>kcal</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($plan['macros'] as $macro)
                                <tr>
                                    <td>{{ $macro['label'] }}</td>
                                    <td>{{ $macro['percent'] }} %</td>
                                    <td>
                                        {{ $macro['grams'] }} g
                                        @isset($macro['g_per_kg'])
                                            <span class="text-xs text-secondary">({{ $number($macro['g_per_kg']) }} g/kg)</span>
                                        @endisset
                                    </td>
                                    <td>{{ $macro['kcal'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-secondary mb-2">
                    @foreach ($plan['limits'] as $limit)
                        {{ $limit['label'] }} {{ $limit['comparator'] === 'max' ? '<' : '≥' }} {{ $limit['amount'] }} {{ $limit['unit'] }}@isset($limit['percent']) ({{ $limit['percent'] }} %)@endisset{{ $loop->last ? '' : ' · ' }}
                    @endforeach
                </p>
                @foreach ($plan['rules'] as $rule)
                    <p class="text-sm mb-1">
                        <span class="text-xs text-secondary">{{ $rule['rule_id'] }}</span>
                        <strong>{{ $rule['title'] }}</strong>
                        <span class="text-xs text-secondary">({{ implode(' · ', $rule['evidence']) }})</span>:
                        {{ $rule['advice'] }}
                    </p>
                @endforeach
                @foreach ($plan['notes'] as $note)
                    <p class="text-xs text-warning mb-0">{{ $note }}</p>
                @endforeach
            @endif
        </div>
    @endif

    @if (! empty($result['indices']))
        <div class="table-responsive">
            <table class="table table-sm mb-1">
                <thead>
                    <tr><th>{{ __('Índice') }}</th><th>{{ __('Valor') }}</th><th>{{ __('Referencia') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($result['indices'] as $index)
                        <tr>
                            <td>{{ $index['label'] }}</td>
                            <td @class(['text-danger font-weight-bold' => $index['high']])>
                                {{ $number($index['value']) }} {{ $index['unit'] }}
                                @isset($index['category'])
                                    <span class="text-xs">({{ $index['category'] }})</span>
                                @endisset
                            </td>
                            <td class="text-secondary">{{ $index['reference'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <p class="text-xs text-secondary">{{ __('Apoyo a la decisión clínica con puntos de corte para adultos; no reemplaza el juicio del profesional.') }}</p>
</div>
