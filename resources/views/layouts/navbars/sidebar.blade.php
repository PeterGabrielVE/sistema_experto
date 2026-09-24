@php
    $item = fn (bool $active) => 'nav-link'.($active ? ' active' : '');
    $page = $activePage ?? '';
@endphp

<aside class="sidenav bg-white navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-4" id="sidenav-main">
  <div class="sidenav-header">
    <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-xl-none" aria-hidden="true" id="iconSidenav"></i>
    <a class="navbar-brand m-0 d-flex align-items-center" href="{{ route('home') }}">
      <img src="{{ asset('assets/img/sauce-logo.png') }}" class="navbar-brand-img h-100" alt="El Sauce">
      <span class="ms-2 font-weight-bold">KATRINA</span>
    </a>
  </div>

  <hr class="horizontal dark mt-0">

  <div class="collapse navbar-collapse w-auto h-auto" id="sidenav-collapse-main">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="{{ $item($page === 'home') }}" href="{{ route('home') }}">
          <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
            <i class="ni ni-tv-2 text-primary text-sm opacity-10"></i>
          </div>
          <span class="nav-link-text ms-1">{{ __('Inicio') }}</span>
        </a>
      </li>

      @can('viewAny', \App\Models\Patient::class)
      <li class="nav-item">
        <a class="{{ $item(in_array($page, ['patient', 'diagnoses.all', 'result'])) }}" href="{{ route('patient.index') }}">
          <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
            <i class="ni ni-single-02 text-success text-sm opacity-10"></i>
          </div>
          <span class="nav-link-text ms-1">{{ __('Pacientes') }}</span>
        </a>
      </li>
      @endcan

      @can('viewAny', \App\Models\Rule::class)
      <li class="nav-item">
        <a class="{{ $item(in_array($page, ['rules', 'recommendation'])) }}" href="{{ route('rules.index') }}">
          <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
            <i class="ni ni-bulb-61 text-warning text-sm opacity-10"></i>
          </div>
          <span class="nav-link-text ms-1">{{ __('Recomendaciones') }}</span>
        </a>
      </li>
      @endcan

      <li class="nav-item">
        <a class="{{ $item($page === 'information') }}" href="{{ route('page.index', 'information') }}">
          <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
            <i class="ni ni-single-copy-04 text-info text-sm opacity-10"></i>
          </div>
          <span class="nav-link-text ms-1">{{ __('Información') }}</span>
        </a>
      </li>

      <li class="nav-item mt-3">
        <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">{{ __('Cuenta') }}</h6>
      </li>

      @can('viewAny', \App\Models\User::class)
      <li class="nav-item">
        <a class="{{ $item($page === 'users') }}" href="{{ route('user.index') }}">
          <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
            <i class="ni ni-badge text-danger text-sm opacity-10"></i>
          </div>
          <span class="nav-link-text ms-1">{{ __('Usuarios') }}</span>
        </a>
      </li>
      @endcan

      <li class="nav-item">
        <a class="{{ $item($page === 'profile') }}" href="{{ route('profile.edit') }}">
          <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
            <i class="ni ni-circle-08 text-dark text-sm opacity-10"></i>
          </div>
          <span class="nav-link-text ms-1">{{ __('Mi perfil') }}</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
          <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
            <i class="ni ni-button-power text-secondary text-sm opacity-10"></i>
          </div>
          <span class="nav-link-text ms-1">{{ __('Salir') }}</span>
        </a>
      </li>
    </ul>
  </div>
</aside>
