{{-- Public, from the reminder e-mail link (US-6.1). No patient data on this page. --}}
@extends('layouts.app', [
    'namePage' => 'Recordatorios de consulta',
    'activePage' => 'reminders',
    'forPatients' => true,
])

@section('content')
    <div class="col-xl-5 col-lg-6 col-md-8">
        <div class="card z-index-0">
            <div class="card-header text-center pt-4 pb-0">
                <h5>{{ __('¿Desea dejar de recibir recordatorios de consulta?') }}</h5>
            </div>
            <div class="card-body text-center">
                <p class="text-sm">{{ __('Ya no le enviaremos correos antes de sus próximas consultas. Si cambia de opinión, pídalo a su profesional en la próxima consulta.') }}</p>
                <form method="post" action="{{ request()->fullUrl() }}">
                    @csrf
                    <button type="submit" class="btn btn-lg btn-primary w-100 mt-3 mb-0">{{ __('Dejar de recibir recordatorios') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
