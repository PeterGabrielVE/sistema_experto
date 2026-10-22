{{-- PortalInvitationMail (US-6.3). Only the fields the mailable passes: no clinical data. --}}
<x-mail::message>
# {{ __('Hola :name', ['name' => $firstName]) }}

{{ __('Su profesional le dio acceso al portal de pacientes, donde podrá ver su próxima consulta y su plan alimentario.') }}

<x-mail::button :url="$url">
{{ __('Crear mi contraseña') }}
</x-mail::button>

{{ __('El enlace es personal y es válido hasta el :date.', ['date' => $expiresAt]) }}

<x-mail::subcopy>
{{ __('Si no esperaba este correo, puede ignorarlo.') }}
</x-mail::subcopy>
</x-mail::message>
