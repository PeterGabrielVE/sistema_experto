{{-- Portal access of the patient (US-6.3): state of the account or of the last invitation. --}}
@php
    $local = fn ($date) => $date?->copy()->setTimezone(config('app.clinic_timezone'))->format('d/m/Y H:i');
    $failedOrExpired = $invitation && ($invitation->status === \App\Models\PatientInvitation::FAILED
        || ($invitation->status === \App\Models\PatientInvitation::SENT && $invitation->isExpired()));
@endphp
<div class="card mt-4" id="patient-portal">
    <div class="card-header pb-0 pt-3 bg-transparent">
        <h6 class="mb-0">{{ __('Portal del paciente') }}</h6>
    </div>
    <div class="card-body p-3">
        @error('portal')
            <p class="text-sm text-danger mb-2">{{ $message }}</p>
        @enderror

        @if ($portalAccount)
            <p class="text-sm mb-1">{{ __('Tiene acceso al portal desde el :date.', ['date' => $local($portalAccount->created_at)]) }}</p>
        @elseif (! $invitation || $invitation->status === \App\Models\PatientInvitation::REVOKED || $invitation->status === \App\Models\PatientInvitation::ACCEPTED)
            <p class="text-sm text-muted mb-1">{{ __('Sin acceso al portal.') }}</p>
        @elseif ($invitation->status === \App\Models\PatientInvitation::QUEUED)
            <p class="text-sm text-muted mb-1">{{ __('Invitación enviándose a :email.', ['email' => $invitation->email]) }}</p>
        @elseif ($invitation->status === \App\Models\PatientInvitation::FAILED)
            <p class="text-sm text-danger mb-1">{{ __('No se pudo enviar la invitación (:date).', ['date' => $local($invitation->failed_at)]) }}</p>
        @elseif ($invitation->isExpired())
            <p class="text-sm text-warning mb-1">{{ __('La invitación venció el :date.', ['date' => $local($invitation->expires_at)]) }}</p>
        @else
            <p class="text-sm mb-1">{{ __('Invitación enviada el :sent; vence el :expires.', ['sent' => $local($invitation->sent_at), 'expires' => $local($invitation->expires_at)]) }}</p>
        @endif

        @if (! $portalAccount)
            @if ($cannotInvite && ! $errors->has('portal'))
                <p class="text-xs text-secondary mb-1">{{ $cannotInvite }}</p>
            @endif
            @can('invitePortal', $patient)
                <form method="post" action="{{ route('patient.portal.invite', $patient) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm mb-0" @disabled($cannotInvite)>
                        {{ $failedOrExpired ? ($invitation->status === \App\Models\PatientInvitation::FAILED ? __('Reintentar') : __('Invitar de nuevo')) : ($invitation && $invitation->status === \App\Models\PatientInvitation::SENT ? __('Reenviar invitación') : __('Invitar al portal')) }}
                    </button>
                </form>
            @endcan
        @endif
    </div>
</div>
