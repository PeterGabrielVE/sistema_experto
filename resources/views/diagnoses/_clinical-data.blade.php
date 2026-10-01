{{-- Measurements and lab results linked to a consultation (result page). --}}
@php
    $value = fn ($v, $unit = '') => $v === null ? '—' : rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',').($unit ? ' '.$unit : '');
@endphp

<div class="mt-4">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <h6 class="heading-small text-muted mb-0">{{ __('Mediciones de la consulta') }}</h6>
        @can('updateClinicalRecord', $patient)
            <a href="{{ route('patient.measurements.create', [$patient, 'diagnosis' => $diagnosis->id]) }}" class="btn btn-sm btn-outline-primary mb-0 ms-auto">{{ __('Registrar medición') }}</a>
        @endcan
    </div>
    @if($measurements->isEmpty())
        <p class="text-sm text-secondary">{{ __('No hay mediciones asociadas a esta consulta.') }}</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>{{ __('Fecha') }}</th><th>{{ __('Peso') }}</th><th>{{ __('Talla') }}</th><th>{{ __('IMC') }}</th>
                        <th>{{ __('Cintura') }}</th><th>{{ __('P. arterial') }}</th><th>{{ __('Glicemia capilar') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($measurements as $m)
                        <tr>
                            <td>{{ $m->measured_at->format('d/m/Y') }}</td>
                            <td>{{ $value($m->weight_kg, 'kg') }}</td>
                            <td>{{ $value($m->height_cm, 'cm') }}</td>
                            <td>{{ $m->bmi() !== null ? $value($m->bmi()).' ('.$m->bmiCategory().')' : '—' }}</td>
                            <td>{{ $value($m->waist_cm, 'cm') }}</td>
                            <td>{{ $m->bloodPressure() ?? '—' }}</td>
                            <td>{{ $value($m->capillary_glucose, 'mg/dL') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center gap-2 mb-2 mt-3">
        <h6 class="heading-small text-muted mb-0">{{ __('Exámenes de la consulta') }}</h6>
        @can('updateClinicalRecord', $patient)
            <a href="{{ route('patient.lab-results.create', [$patient, 'diagnosis' => $diagnosis->id]) }}" class="btn btn-sm btn-outline-primary mb-0 ms-auto">{{ __('Registrar examen') }}</a>
        @endcan
    </div>
    @if($labResults->isEmpty())
        <p class="text-sm text-secondary">{{ __('No hay exámenes asociados a esta consulta.') }}</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>{{ __('Fecha') }}</th><th>{{ __('Glicemia') }}</th><th>{{ __('Insulina') }}</th><th>{{ __('HbA1c') }}</th>
                        <th>{{ __('Triglicéridos') }}</th><th>{{ __('HDL') }}</th><th>{{ __('HOMA-IR') }}</th><th>{{ __('TyG') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($labResults as $r)
                        <tr>
                            <td>{{ $r->taken_at->format('d/m/Y') }}</td>
                            <td>{{ $value($r->fasting_glucose, 'mg/dL') }}</td>
                            <td>{{ $value($r->fasting_insulin, 'µU/mL') }}</td>
                            <td>{{ $value($r->hba1c, '%') }}</td>
                            <td>{{ $value($r->triglycerides, 'mg/dL') }}</td>
                            <td>{{ $value($r->hdl, 'mg/dL') }}</td>
                            <td>{{ $r->homaIr() !== null ? number_format($r->homaIr(), 2, ',', '.') : '—' }}</td>
                            <td>{{ $r->tygIndex() !== null ? number_format($r->tygIndex(), 2, ',', '.') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
