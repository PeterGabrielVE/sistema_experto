{{-- Public, after confirming the unsubscribe (US-6.1). No patient data on this page. --}}
@extends('layouts.app', [
    'namePage' => 'Recordatorios de consulta',
    'activePage' => 'reminders',
    'forPatients' => true,
])

@section('content')
    <div class="col-xl-5 col-lg-6 col-md-8">
        <div class="card z-index-0">
            <div class="card-body text-center py-5">
                <h5>{{ __('Listo') }}</h5>
                <p class="text-sm mb-0">{{ __('No recibirá más recordatorios de consulta por correo.') }}</p>
            </div>
        </div>
    </div>
@endsection
