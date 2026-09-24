<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/apple-icon.png') }}">
  <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
  <title>{{ $namePage ?? 'KATRINA' }} · KATRINA</title>

  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
  {{-- Legacy Now UI icon font, still used by some views --}}
  <link href="{{ asset('assets/css/now-ui-icons.css') }}" rel="stylesheet" />
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.css">

  {{-- Argon Dashboard 2 + Bootstrap 5 --}}
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
  @stack('css')
</head>

<body class="{{ auth()->check() ? 'g-sidenav-show' : '' }} bg-gray-100 {{ $class ?? '' }}">
  @auth
    @include('layouts.page_template.auth')
  @endauth
  @guest
    @include('layouts.page_template.guest')
  @endguest

  @routes
  {{-- jQuery before Bootstrap (module) so Bootstrap 5 registers its jQuery plugins --}}
  <script src="{{ asset('assets/js/core/jquery.min.js') }}"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.js"></script>
  @stack('js')
</body>

</html>
