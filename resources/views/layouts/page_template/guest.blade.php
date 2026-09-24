<main class="main-content mt-0">
    {{-- Flat header band (Argon sign-in layout, no gradient) --}}
    <div class="page-header align-items-start min-vh-50 pt-5 pb-11 m-3 border-radius-lg bg-primary">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center mx-auto">
                    <h1 class="text-white mb-2 mt-5">KATRINA</h1>
                    <p class="text-lead text-white">
                        {{ __('Sistema experto de recomendaciones dietéticas para pacientes con resistencia a la insulina') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row mt-lg-n10 mt-md-n11 mt-n10 justify-content-center">
            @yield('content')
        </div>
    </div>
    <div class="container mt-4">
        @include('layouts.footer')
    </div>
</main>
