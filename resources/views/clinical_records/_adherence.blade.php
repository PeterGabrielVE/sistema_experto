{{-- How the patient follows the plan, from the portal records (US-6.2/AC-4). A week without marked
     meals is "Sin dato", never 0 %; days without record are shown apart, never as not followed. --}}
@php
    $range = fn (string $from, string $to) => \Illuminate\Support\Carbon::parse($from)->format('d/m').' – '.\Illuminate\Support\Carbon::parse($to)->format('d/m');
@endphp
<div class="card mt-4" id="adherence">
    <div class="card-header pb-0 pt-3 bg-transparent">
        <h6 class="mb-0">{{ __('Cumplimiento del plan') }}</h6>
        <p class="text-xs text-secondary mb-0">{{ __('Registrado por el paciente en el portal. Cumplí cuenta 1, en parte 0,5 y no cumplí 0, sobre las comidas marcadas.') }}</p>
    </div>
    <div class="card-body p-3">
        @if (! $adherence)
            <p class="text-sm text-muted mb-0">{{ __('Sin plan alimentario guardado.') }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Semana') }}</th>
                            <th class="text-right">{{ __('Cumplimiento') }}</th>
                            <th>{{ __('Comidas marcadas') }}</th>
                            <th>{{ __('Días sin registro') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($adherence as $week)
                            <tr>
                                <td>{{ $range($week['from'], $week['to']) }}</td>
                                <td class="text-right">
                                    @if ($week['percent'] === null)
                                        <span class="text-muted">{{ __('Sin dato') }}</span>
                                    @else
                                        <strong>{{ number_format($week['percent'], 1, ',', '.') }} %</strong>
                                    @endif
                                </td>
                                <td>{{ trans_choice(':count comida marcada|:count comidas marcadas', $week['marked']) }}</td>
                                <td>{{ trans_choice(':count día sin registro|:count días sin registro', $week['days_without_record']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
