{{-- Patient portal (US-6.3): no sidebar nor anything of the team, a header with the exit. --}}
<main class="main-content mt-0">
    <nav class="navbar navbar-light bg-white shadow-sm px-3">
        <span class="navbar-brand mb-0 h6">{{ __('Portal de pacientes') }}</span>
        <form action="{{ route('logout') }}" method="POST" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm mb-0">{{ __('Salir') }}</button>
        </form>
    </nav>
    <div class="container py-4">
        @yield('content')
    </div>
</main>
