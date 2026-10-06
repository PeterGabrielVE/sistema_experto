{{-- Generated menu in the PDF (dompdf: plain tables, no flexbox). --}}
@php
    $number = fn ($v) => rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',');
    $portions = fn ($p) => $number($p).' '.($p == 1 ? 'porción' : 'porciones');
@endphp

<div>
    <h4 class="m-3 p-3">Plan alimentario</h4>
</div>

@if (! $mealPlan || empty($mealPlan['meals']))
    <p style="font-size: 12px;">
        {{ $mealPlan ? implode(' ', $mealPlan['notes']) : 'El plan alimentario no está disponible: la consulta necesita requerimiento energético y gramos de macronutrientes, y el sistema experto debe estar en línea.' }}
    </p>
@else
    <p style="font-size: 11px;">
        Energía {{ $number($mealPlan['totals']['energy']) }} kcal ·
        Carbohidratos {{ $number($mealPlan['totals']['carbohydrates']) }} g ·
        Proteínas {{ $number($mealPlan['totals']['proteins']) }} g ·
        Grasas {{ $number($mealPlan['totals']['fats']) }} g
        @isset($mealPlan['totals']['glycemic_load'])
            · Carga glucémica {{ $number($mealPlan['totals']['glycemic_load']) }}
        @endisset
    </p>

    @foreach ($mealPlan['meals'] as $meal)
        <table class="contenido" border="0" width="100%" style="margin-bottom: 10px;">
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

    <p style="font-size: 10px;">Porciones de intercambio. Puede reemplazar un alimento por otro del mismo grupo en la misma cantidad de porciones.</p>
@endif
<hr style="color: #3989c6;">
