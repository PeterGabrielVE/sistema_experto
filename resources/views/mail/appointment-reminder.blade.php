{{-- AppointmentReminderMail (US-6.1). Only the fields the mailable passes: no clinical data. --}}
<x-mail::message>
# {{ __('Hola :name', ['name' => $firstName]) }}

{{ __('Le recordamos su próxima consulta:') }}

- **{{ __('Fecha') }}:** {{ $date }}
- **{{ __('Hora') }}:** {{ $time }}
@if($professional)
- **{{ __('Profesional') }}:** {{ $professional }}
@endif
@if($notes)
- **{{ __('Indicaciones') }}:** {{ $notes }}
@endif

{{ __('Si no puede asistir, avísenos para darle otra hora.') }}

<x-mail::subcopy>
{{ __('Si no desea recibir estos recordatorios:') }} [{{ __('Dejar de recibir recordatorios') }}]({{ $unsubscribeUrl }})
</x-mail::subcopy>
</x-mail::message>
