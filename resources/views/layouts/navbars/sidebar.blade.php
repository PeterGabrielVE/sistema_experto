<div class="sidebar" data-color="blue">
  <!--
    Tip 1: You can change the color of the sidebar using: data-color="blue | green | orange | red | yellow"
-->
  <div class="logo">
    <a href="#" class="simple-text logo-mini">
      {{ __('SE') }}
    </a>
    <a href="#" class="simple-text logo-normal">
      {{ __('Sistema Experto') }}
    </a>
  </div>
  <div class="sidebar-wrapper" id="sidebar-wrapper">
    <ul class="nav">
      <li class="@if ($activePage == 'home') active @endif">
        <a href="{{ route('home') }}">
          <i class="now-ui-icons design_app"></i>
          <p>{{ __('Inicio') }}</p>
        </a>
      </li>
      <li>
        <a data-toggle="collapse" href="#laravelExamples">
            <i class="now-ui-icons users_single-02"></i>
          <p>
            {{ __("Usuarios") }}
            <b class="caret"></b>
          </p>
        </a>
        <div class="collapse show" id="laravelExamples">
          <ul class="nav">
            <li class="@if ($activePage == 'profile') active @endif">
              <a href="{{ route('profile.edit') }}">
                <i class="now-ui-icons users_single-02"></i>
                <p> {{ __("Perfil Usuario") }} </p>
              </a>
            </li>
            <li class="@if ($activePage == 'users') active @endif">
              <a href="{{ route('user.index') }}">
                <i class="now-ui-icons design_bullet-list-67"></i>
                <p> {{ __("Usuarios") }} </p>
              </a>
            </li>
          </ul>
        </div>
      <li class="@if ($activePage == 'patient') active @endif">
        <a href="{{ route('patient.index','patient') }}">
            <i class="now-ui-icons design_bullet-list-67"></i>
              <p> {{ __("Pacientes") }} </p>
        </a>
      </li>
      <li class = "@if ($activePage == 'recommendation') active @endif">
        <a href="{{ route('recommendation.index') }}">
          <i class="now-ui-icons tech_tv"></i>
          <p>{{ __('Recomendaciones') }}</p>
        </a>
      </li>
      <li class = "@if ($activePage == 'schedule') active @endif">
        <a href="{{ route('schedule.index') }}">
          <i class="now-ui-icons ui-1_calendar-60"></i>
          <p>{{ __('Horarios') }}</p>
        </a>
      </li>
      <li class = "@if ($activePage == 'rules') active @endif">
        <a href="{{ route('rules.index') }}">
          <i class="now-ui-icons business_bulb-63"></i>
          <p>{{ __('Regla de Conocimiento') }}</p>
        </a>
      </li>

    </ul>
  </div>
</div>
