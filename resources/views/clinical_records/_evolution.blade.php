{{-- Weight and HOMA-IR evolution (US-5.1). Data from patient.evolution, drawn by patient-evolution-chart.js. --}}
<div class="card mt-4" id="patient-evolution" data-url="{{ route('patient.evolution', $patient) }}">
    <div class="card-header pb-0 pt-3 bg-transparent d-flex flex-wrap align-items-center gap-2">
        <h6 class="mb-0">{{ __('Evolución del peso y HOMA-IR') }}</h6>
        <div class="btn-group btn-group-sm ms-auto flex-wrap" role="group" aria-label="{{ __('Período') }}">
            @foreach (['3m' => __('Últimos 3 meses'), '6m' => __('Últimos 6 meses'), '12m' => __('Últimos 12 meses'), 'all' => __('Todo el historial')] as $period => $label)
                <button type="button" class="btn btn-outline-primary mb-0 {{ $period === 'all' ? 'active' : '' }}" data-period="{{ $period }}" aria-pressed="{{ $period === 'all' ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>
    <div class="card-body p-3">
        <div class="chart">
            <canvas id="patient-evolution-chart" class="chart-canvas" height="300"></canvas>
        </div>

        <p class="text-sm text-muted mb-1 d-none" data-evolution-message="weight">
            {{ __('Peso: se necesitan al menos dos mediciones con peso en el período para ver su evolución.') }}
            @can('updateClinicalRecord', $patient)
                <a href="{{ route('patient.measurements.create', $patient) }}">{{ __('Registrar medición') }}</a>
            @endcan
        </p>
        <p class="text-sm text-muted mb-1 d-none" data-evolution-message="homa_ir">
            {{ __('HOMA-IR: se necesitan al menos dos exámenes con glicemia e insulina en ayunas en el período para ver su evolución.') }}
            @can('updateClinicalRecord', $patient)
                <a href="{{ route('patient.lab-results.create', $patient) }}">{{ __('Registrar examen') }}</a>
            @endcan
        </p>
        <p class="text-sm text-muted mb-1 d-none" data-evolution-excluded>
            {{ __('Exámenes del período sin glicemia o insulina en ayunas (no se grafican):') }}
            <span data-evolution-excluded-count></span>
        </p>
        <p class="text-sm text-danger mb-0 d-none" data-evolution-error>
            {{ __('No se pudo cargar la evolución. Recargue la página para intentarlo de nuevo.') }}
        </p>
    </div>
</div>

@push('js')
    @vite('resources/js/patient-evolution-chart.js')
@endpush
