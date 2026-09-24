<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>

{{-- Flat header band (Argon layout, no gradient) --}}
<div class="app-header-band bg-primary position-absolute w-100"></div>

@include('layouts.navbars.sidebar')

<main class="main-content position-relative border-radius-lg">
    @include('layouts.navbars.navs.auth')

    <div class="container-fluid py-4">
        @yield('content')
        @include('layouts.footer')
    </div>
</main>
