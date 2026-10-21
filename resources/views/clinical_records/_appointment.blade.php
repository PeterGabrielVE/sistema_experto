{{-- Next appointment (US-6.1): shown, changed and cancelled here, or set with a consultation. --}}
@php
    $local = $appointment?->localScheduledAt();
@endphp
<div class="card mt-4" id="next-appointment">
    <div class="card-header pb-0 pt-3 bg-transparent">
        <h6 class="mb-0">{{ __('Próxima consulta') }}</h6>
    </div>
    <div class="card-body p-3">
        @if($appointment)
            <p class="mb-1">
                <strong>{{ $local->format('d/m/Y H:i') }}</strong>
                · {{ __('Profesional: :name', ['name' => $appointment->doctor?->name ?? '—']) }}
            </p>
            @if($appointment->notes)
                <p class="mb-1">{{ __('Indicaciones:') }} {{ $appointment->notes }}</p>
            @endif
        @else
            <p class="text-muted mb-1">{{ __('Sin próxima consulta programada.') }}</p>
        @endif

        {{-- Consent to reminders by e-mail (D-3), changed in the patient's data. --}}
        <p class="text-sm mb-1">
            @switch($patient->reminderSkipReason())
                @case('no_email')
                    <span class="text-warning">{{ __('El paciente no tiene correo registrado.') }}</span>
                    @break
                @case('unsubscribed')
                    <span class="text-warning">{{ __('El paciente se dio de baja el :date.', ['date' => $patient->reminders_unsubscribed_at->copy()->setTimezone(config('app.clinic_timezone'))->format('d/m/Y')]) }}</span>
                    @break
                @case('no_consent')
                    <span class="text-muted">{{ __('El paciente no ha aceptado recibir recordatorios por correo.') }}</span>
                    @break
                @default
                    <span class="text-success">{{ __('Recordatorios por correo activados.') }}</span>
            @endswitch
            @can('update', $patient)
                <a href="{{ route('patient.edit', $patient) }}" class="ml-1">{{ __('Cambiar en los datos del paciente') }}</a>
            @endcan
        </p>

        @can('update', $patient)
            <form method="post" action="{{ route('patient.appointment.update', $patient) }}" class="row align-items-end mt-3" autocomplete="off">
                @csrf
                @method('put')
                <div class="form-group col-md-3">
                    <label class="form-control-label" for="input-scheduled-at">{{ $appointment ? __('Cambiar fecha y hora') : __('Fecha y hora') }}</label>
                    <input type="datetime-local" name="scheduled_at" id="input-scheduled-at" class="form-control" required
                           value="{{ old('scheduled_at', $local?->format('Y-m-d\TH:i')) }}" min="{{ now(config('app.clinic_timezone'))->format('Y-m-d\TH:i') }}">
                    @include('alerts.feedback', ['field' => 'scheduled_at'])
                </div>
                <div class="form-group col-md-6">
                    <label class="form-control-label" for="input-appointment-notes">{{ __('Indicaciones') }}</label>
                    <input type="text" name="notes" id="input-appointment-notes" class="form-control" maxlength="500"
                           value="{{ old('notes', $appointment?->notes) }}" placeholder="{{ __('Por ejemplo: venir en ayuno de 8 horas para el examen') }}">
                    <small class="text-muted">{{ __('Se envían por correo al paciente: no incluya diagnósticos ni resultados.') }}</small>
                    @include('alerts.feedback', ['field' => 'notes'])
                </div>
                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-primary btn-round mb-0">{{ $appointment ? __('Guardar cambios') : __('Programar') }}</button>
                </div>
            </form>

            @if($appointment)
                <form method="post" action="{{ route('patient.appointment.destroy', $patient) }}"
                      onsubmit="return confirm(@js(__('¿Cancelar la próxima consulta?')))">
                    @csrf
                    @method('delete')
                    <button type="submit" class="btn btn-outline-danger btn-sm mb-0">{{ __('Cancelar consulta') }}</button>
                </form>
            @endif
        @endcan
    </div>
</div>
