{{-- Plan for the patient (US-5.2), converted by DomPDF: plain tables and inline styles, no flexbox.
     Only what PatientPlanDocument passes: no RUT, address, BMI, category nor clinical record but the allergies. --}}
@php
    $number = fn ($v, $decimals = 1) => rtrim(rtrim(number_format((float) $v, $decimals, ',', '.'), '0'), ',');
    $portions = fn ($p) => $p == 0.5 ? '½ porción' : $number($p).' '.($p == 1 ? 'porción' : 'porciones');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ __('Plan alimentario') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 14px; margin: 16px 0 4px; color: #2b6cb0; }
        table.menu { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.menu th { text-align: left; background: #edf2f7; padding: 4px 6px; }
        table.menu td { padding: 3px 6px; border-bottom: 1px solid #e2e8f0; }
        .muted { color: #666; }
        .footer { margin-top: 24px; border-top: 1px solid #cbd5e0; padding-top: 6px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td valign="middle">
                @if ($clinic['logo'])
                    <img src="{{ $clinic['logo'] }}" alt="{{ $clinic['name'] }}" style="height: 60px;">
                @else
                    <h1>{{ $clinic['name'] }}</h1>
                @endif
            </td>
            <td align="right" valign="middle">
                <h1>{{ __('Plan alimentario') }}</h1>
                <p class="muted" style="margin: 4px 0 0;">
                    {{ $patientName }}<br>
                    {{ __('Consulta del :date', ['date' => $date]) }}
                    @if ($professional)
                        <br>{{ __('Profesional: :name', ['name' => $professional]) }}
                    @endif
                </p>
            </td>
        </tr>
    </table>

    @foreach ($days as $day)
        <div @if (! $loop->first) style="page-break-before: always;" @endif>
            @if (count($days) > 1)
                <h2>{{ __('Día :n', ['n' => $day['day']]) }}</h2>
            @endif
            @if (count($day['totals']) === 4)
                <p class="muted">
                    {{ __('Energía') }} {{ $number($day['totals']['energy'], 0) }} kcal ·
                    {{ __('Carbohidratos') }} {{ $number($day['totals']['carbohydrates']) }} g ·
                    {{ __('Proteínas') }} {{ $number($day['totals']['proteins']) }} g ·
                    {{ __('Grasas') }} {{ $number($day['totals']['fats']) }} g
                </p>
            @endif

            @foreach ($day['meals'] as $meal)
                <table class="menu">
                    <thead><tr><th colspan="2">{{ $meal['label'] }}</th></tr></thead>
                    <tbody>
                        @foreach ($meal['items'] as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td align="right" width="35%">
                                    {{ $portions((float) $item['portions']) }}@if (! empty($item['grams'])) ({{ $number($item['grams']) }} g)@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    @endforeach

    @if ($recommendations)
        <h2>{{ __('Recomendaciones') }}</h2>
        <ul>
            @foreach ($recommendations as $recommendation)
                <li>{{ $recommendation }}</li>
            @endforeach
        </ul>
    @endif

    <h2>{{ __('Indicaciones generales') }}</h2>
    <p>@include('pdf._general-advice')</p>

    <h2>{{ __('Alergias e intolerancias consideradas') }}</h2>
    <p>{{ $allergies ?: __('Sin alergias ni intolerancias registradas.') }}</p>

    <div class="footer">
        {{ $clinic['name'] }}
        @foreach ($clinic['contact'] as $contact)
            · {{ $contact }}
        @endforeach
    </div>
</body>
</html>
