{{-- Menu proposal in the PDF (dompdf: plain tables, no flexbox). --}}
@php
    $number = fn ($v) => rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',');
    $portions = fn ($p) => $number($p).' '.($p == 1 ? 'porción' : 'porciones');
@endphp

<div>
    <h4 class="m-3 p-3">Propuesta de menú</h4>
</div>

@if (! $mealPlan || empty($mealPlan['days']))
    <p style="font-size: 12px;">
        {{ $mealPlan ? implode(' ', $mealPlan['notes']) : 'La propuesta de menú no está disponible: la consulta necesita requerimiento energético y gramos de macronutrientes, y el sistema experto debe estar en línea.' }}
    </p>
@else
    @foreach ($mealPlan['days'] as $day)
        @if (count($mealPlan['days']) > 1)
            <h5 style="margin: 8px 0 4px;">Día {{ $day['day'] }}</h5>
        @endif
        <p style="font-size: 11px;">
            Energía {{ $number($day['totals']['energy']) }} kcal ·
            Carbohidratos {{ $number($day['totals']['carbohydrates']) }} g ·
            Proteínas {{ $number($day['totals']['proteins']) }} g ·
            Grasas {{ $number($day['totals']['fats']) }} g
            @isset($day['totals']['glycemic_load'])
                · Carga glucémica {{ $number($day['totals']['glycemic_load']) }}
            @endisset
        </p>

        @foreach ($day['meals'] as $meal)
            <table class="contenido" border="0" width="100%" style="margin-bottom: 8px;">
                <thead style="font-size: 12px; background: #f3f3f3; color: #000;">
                    <tr>
                        <th colspan="3">{{ $meal['label'] }} <span style="font-weight: normal;">({{ $number($meal['totals']['energy']) }} kcal)</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meal['items'] as $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td style="width: 90px;">{{ $item['grams'] ? $item['grams'].' g' : $portions($item['portions']) }}</td>
                            <td style="width: 110px;">{{ $portions($item['portions']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
        @if (! $loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <p style="font-size: 10px;">Porciones de intercambio. Puede reemplazar un alimento por otro del mismo grupo en la misma cantidad de porciones.</p>
@endif
<hr style="color: #3989c6;">
