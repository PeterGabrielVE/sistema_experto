{{-- Home of the patient portal (US-6.3). The next appointment and the saved meal plan come in TK-6.3.4. --}}
@extends('layouts.app', [
    'namePage' => 'Portal de pacientes',
    'activePage' => 'portal',
])

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="mb-0">{{ __('Hola :name', ['name' => $patient->first_name]) }}</h4>
        </div>
    </div>
@endsection
