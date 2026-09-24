<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" data-scroll="false">
  <div class="container-fluid py-1 px-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-white" href="{{ route('home') }}">KATRINA</a></li>
        <li class="breadcrumb-item text-sm text-white active" aria-current="page">{{ $namePage ?? '' }}</li>
      </ol>
      <h6 class="font-weight-bolder text-white mb-0">{{ $namePage ?? '' }}</h6>
    </nav>

    <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
      <div class="ms-md-auto pe-md-3 d-flex align-items-center"></div>
      <ul class="navbar-nav justify-content-end">
        <li class="nav-item d-flex align-items-center">
          <a href="{{ route('downloadManual') }}" class="nav-link text-white font-weight-bold px-0 me-3" title="{{ __('Ayuda en línea') }}">
            <i class="fa fa-question-circle me-sm-1"></i>
            <span class="d-sm-inline d-none">{{ __('Ayuda') }}</span>
          </a>
        </li>
        <li class="nav-item dropdown d-flex align-items-center">
          <a href="#" class="nav-link text-white font-weight-bold px-0" id="accountMenu" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-user me-sm-1"></i>
            <span class="d-sm-inline d-none">{{ auth()->user()->name }}</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end px-2 py-3" aria-labelledby="accountMenu">
            <li class="px-3 pb-2 text-xs text-secondary">{{ auth()->user()->role()?->label() ?? __('Sin rol') }}</li>
            <li><a class="dropdown-item border-radius-md" href="{{ route('profile.edit') }}">{{ __('Mi perfil') }}</a></li>
            <li>
              <a class="dropdown-item border-radius-md" href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Salir') }}</a>
            </li>
          </ul>
        </li>
        <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
          <a href="#" class="nav-link text-white p-0" id="iconNavbarSidenav" aria-label="{{ __('Menú') }}">
            <div class="sidenav-toggler-inner">
              <i class="sidenav-toggler-line bg-white"></i>
              <i class="sidenav-toggler-line bg-white"></i>
              <i class="sidenav-toggler-line bg-white"></i>
            </div>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
