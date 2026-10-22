{{-- Public: an invitation link that no longer works (US-6.3/AC-3). Nothing of the patient on this page. --}}
@extends('layouts.app', [
    'namePage' => 'Portal de pacientes',
    'activePage' => 'portal',
    'forPatients' => true,
])

@section('content')
    <div class="col-xl-5 col-lg-6 col-md-8">
        <div class="card z-index-0">
            <div class="card-body text-center py-5">
                <h5>{{ __('Este enlace ya no sirve.') }}</h5>
                <p class="text-sm mb-0">
                    {{ __('Puede haber vencido o ya haberse usado.') }}
                    {{ __('Pida una nueva invitación a su profesional.') }}
                </p>
            </div>
        </div>
    </div>
@endsection
