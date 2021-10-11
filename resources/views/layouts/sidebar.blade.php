@auth

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion sidebar-menu" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('home') }}">
        <div class="row text-center">
            <div class="col-12">
                <div class="sidebar-brand-icon">
                    <img src="{{ asset('img/SPARE_PARTS_SEEKER_LOGO.png') }}" alt="{{ config('app.name', 'Laravel') }}"
                        class="logo-sidebar">
                </div>
            </div>
            <div class="col-12 pt-2">
                <div class="sidebar-brand-text ">{{ config('app.name', 'Laravel') }}</div>
            </div>
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item nav-seeker {{ active_class(Route::is('home')) }} {{ seleted_option(Route::is('home')) }}">
        <a class="nav-link " href="{{ route('home') }}">
            <i class="fas fa-home"></i>
            <span>{{ __('Inicio') }}</span></a>
    </li>
    <hr class="sidebar-divider my-0">
    <li class="nav-item nav-seeker {{ active_class(Route::is('organizations.listView')) }}">
        <a class="nav-link " href="{{ route('organization.listView') }}">
            <i class="fas fa-home"></i>
            <span>{{ __('organizations') }}</span>
        </a>
    </li>
    <hr class="sidebar-divider my-0">
    <li
        class="nav-item {{ active_class(Route::is('notificaciones.index')) }} {{ seleted_option(Route::is('notificaciones.index')) }}">
        <a class="nav-link nav-seeker" href="{{ route('notificaciones.index') }}">
            <i class="far fa-bell"></i>
            <span>{{ __('Notificaciones') }}</span>
            @if (count(auth()->user()->unreadNotifications))
                <span class="badge badge-light badge-counter position-relative ml-2">
                {{ count(auth()->user()->unreadNotifications) }}
            </span>
            @endif
        </a>
    </li>
    <hr class="sidebar-divider my-0">
    @if (in_array(Auth::user()->rol_id, [1, 2, 3, 8, 12, 14]))
    <li class="settings-items nav-item  {{Route::is('budgets.manually.create') || Route::is('budgets.manually.all') || Route::is('presupuestos.index') || Route::is('budgets.manually.show') || Route::is('detalle-presupuestos.index') ? 'show' : ''}}">
        <a class="nav-link collapsed nav-seeker" href="#" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true"
        aria-controls="collapseOne">
            <i class="fas fa-fw fa-tasks"></i>
            <span>{{ __('Presupuestos') }}</span>
        </a>
        <div id="collapseOne"
            class="collapse sub-item-settings {{ Route::is('budgets.manually.create') || Route::is('budgets.manually.all') || Route::is('presupuestos.index') || Route::is('budgets.manually.show') || Route::is('detalle-presupuestos.index') ? 'show' : '' }} sidebar-submenu" >
            @if (in_array(Auth::user()->rol_id, [1, 14]))
            <div class="nav-item">
                <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('budgets.manually.create')) }} {{ seleted_option(Route::is('budgets.manually.create')) }}" href="{{ route('budgets.manually.create') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">
                    {{ __('newBudgetRequest') }}
                </a>
            </div>
            @endif
            @if (in_array(Auth::user()->rol_id, [1, 3, 5, 12, 14]))
            <div class="nav-item">
                <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('presupuestos.index') || Route::is('detalle-presupuestos.index')) }} {{ seleted_option(Route::is('presupuestos.index') || Route::is('detalle-presupuestos.index')) }}" href="{{ route('presupuestos.index') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">
                    {{ __('automaticBudgets') }}
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('budgets.manually.all') || Route::is('budgets.manually.show')) }} {{ seleted_option(Route::is('budgets.manually.all') || Route::is('budgets.manually.show')) }}" href="{{ route('budgets.manually.all') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">
                    {{ __('manuallyBudgets') }}
                </a>
            </div>
            @endif
            @if (in_array(Auth::user()->rol_id, [8]))
            <div class="nav-item">
                <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('presupuestos.index')) }} {{ seleted_option(Route::is('presupuestos.index')) }}" href="{{ route('presupuestos.index') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">
                    {{ __('Consultas Proveedor') }}
                </a>
            </div>
            @endif
        </div>
    </li>
    <hr class="sidebar-divider my-0">
    @endif
    @if (in_array(Auth::user()->rol_id, [1, 2, 3, 8, 9, 12, 14]))
    <li class="settings-items nav-item  {{ Request::path() == 'admin/pedidos' ? 'show' : null }} {{ Route::is('pedidos.showById') ? 'show' : null }} {{ Route::is('pedidos.show') ? 'show' : null }}">

        <a class="nav-link collapsed nav-seeker" href="#" data-toggle="collapse" data-target="#collapseThree" aria-expanded="true"
            aria-controls="collapseThree">
            <i class="fas fa-fw fa-shopping-cart"></i>
            <span>{{ __('Pedidos') }}</span>
        </a>
        <div id="collapseThree"
            class="collapse sub-item-settings  {{ Request::path() == 'admin/pedidos' ? 'show' : null }} {{ Route::is('pedidos.showById') ? 'show' : null }} {{ Route::is('pedidos.show') ? 'show' : null }} {{ Route::is('orders.manually.show') ? 'show' : null }} {{ Route::is('orders.manually.all') ? 'show' : null }}">
            @if (in_array(Auth::user()->rol_id, [1, 2, 3, 12, 14]))
            <div class="nav-item">
                <a class="nav-link collapsed nav-seeker {{ Route::is('pedidos.index') ? 'item-settings-active' : null }} {{ Route::is('pedidos.show') ? 'item-settings-active' : null }} {{ seleted_option(Route::is('pedidos.index')) }} {{ seleted_option(Route::is('pedidos.show')) }}" href="{{ route('pedidos.index') }}"
                id="orders_consultation" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Automatic Orders Consultation') }}</a>
            </div>
            <div class="nav-item">
                <a class="nav-link collapsed nav-seeker {{ Route::is('orders.manually.all') ? 'item-settings-active' : null }} {{ Route::is('orders.manually.show') ? 'item-settings-active' : null }} {{ seleted_option(Route::is('orders.manually.all')) }} {{ seleted_option(Route::is('orders.manually.show')) }}" href="{{ route('orders.manually.all') }}"
                id="orders_consultation" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Manually Orders Consultation') }}</a>
            </div>
            @elseif (in_array(Auth::user()->rol_id, [8, 9]))
            <div class="nav-item">
                <a class="nav-link collapsed nav-seeker {{ Route::is('pedidos.index') ? 'item-settings-active' : null }}" href="{{ route('pedidos.index') }}" id="consulta_pedido" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Consultas') }}</a>
            </div>
            @endif
        </div>
    </li>

    @endif
    <hr class="sidebar-divider my-0">
    @if (in_array(Auth::user()->rol_id, [1, 8, 12, 13, 15]))
    <li class="nav-item settings-items  {{ Request::path() == 'admin/informes' ? 'show' : null }} {{ Request::path() == 'admin/informesCGP' ? 'show' : null }} {{ seleted_option(Route::is(Request::path() == 'admin/informes')) }} {{ active_class(Route::is('report')) }}">
        <a class="nav-link nav-seeker" href="{{ route('report') }}">
            <i class="fas fa-fw fa-file-invoice"></i>
            <span>{{ __('Informes') }}</span>
        </a>
    </li>
    <hr class="sidebar-divider my-0">
    <li class="nav-item {{ active_class(Route::is('statistics')) }} {{ seleted_option(Route::is('statistics')) }}">
        <a class="nav-link nav-seeker" href="{{ route('statistics') }}">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>{{ __('Estadísticas') }}</span></a>
    </li>
    <hr class="sidebar-divider my-0">
    @endif
    @if (in_array(Auth::user()->rol_id, [1, 8, 12, 15]))
    <li class="settings-items nav-item {{ Route::is('inventario.index') ? 'show' : null }} {{ Route::is('inventario_pro.index') ? 'show' : null }} {{ Route::is('detalle-inventario') ? 'show' : null }} {{ Route::is('providerStock') ? 'show' : null }} {{ Route::is('showPayment') ? 'show' : null }}">
        <a class="nav-link nav-seeker collapsed" href="#" data-toggle="collapse" data-target="#collapseFour" aria-expanded="true"
            aria-controls="collapseFour">
            <i class="fas fa-fw fa-warehouse"></i>
            <span>{{ __('Inventario') }}</span>
        </a>
        <div id="collapseFour"
            class="collapse sub-item-settings {{ active_class(Route::is('inventario.index')) }} {{ active_class(Route::is('inventario_pro.index')) }} {{ active_class(Route::is('detalle-inventario')) }} {{ active_class(Route::is('providerStock')) }} {{ active_class(Route::is('showPayment')) }}">
            <div class="nav-item">
                <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('inventario.index')) }} {{ active_class(Route::is('detalle-inventario')) }} {{ seleted_option(Route::is('inventario.index')) }} {{ seleted_option(Route::is('detalle-inventario')) }}" href="{{ route('inventario.index') }}" id="inventario_index" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Consulta General') }} </a>
            </div>
            @if (in_array(Auth::user()->rol_id, [1, 12, 15]))
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('inventario_pro.index')) }} {{ active_class(Route::is('providerStock')) }} {{ seleted_option(Route::is('inventario_pro.index')) }} {{ seleted_option(Route::is('providerStock')) }}" href="{{ route('inventario_pro.index') }}" id="inventario_pro_index" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem"> {{ __('Consulta Proveedores') }}</a>
                </div>
            @endif
            @if (in_array(Auth::user()->rol_id, [1]))
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('showPayment')) }} {{ active_class(Route::is('providerStock')) }} {{ seleted_option(Route::is('showPayment')) }} {{ seleted_option(Route::is('providerStock')) }}" href="{{ route('showPayment') }}" id="showPayment" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Gestion') }}</a>
                </div>
            @endif
        </div>
    </li>
    <hr class="sidebar-divider my-0">
    @endif

    @if (in_array(Auth::user()->rol_id, [1, 8, 9, 12]))
    <li class="nav-item  {{ active_class(Route::is('incidencias.index')) }} {{ active_class(Route::is('incidencias.show')) }} {{ active_class(strpos(Request::path(), 'admin/gestion/') === 0) }}">
        <a class="nav-link nav-seeker" href="{{ route('incidencias.index') }}">
            <i class="fas fa-fw fa-exclamation-triangle"></i>
            <span>{{ __('Incidencias') }}</span>
        </a>

    </li>
    @endif

    @if (in_array(Auth::user()->rol_id, [1, 12]))
        <hr class="sidebar-divider my-0">
        <li id="settings-items-configurations" class="settings-items nav-item {{ Route::is('parameterslogs.index') ? 'show' : null }} {{ Route::is('nuevo.usuario') ? 'show' : null }} {{ Route::is('roles.create') ? 'show': null }}   {{ Route::is('nuevo.cliente') ? 'show' : null }} {{ Route::is('clientes.index') ? 'show': null }} {{ Route::is('create.provider') ? 'show': null }} {{ Route::is('proveedores.index') ? 'show': null }} {{ Route::is('importer') ? 'show': null }}">
            <a class="nav-link collapsed nav-seeker" href="#"
                data-toggle="collapse"
                data-target="#collapseSixdd"
                aria-expanded="true"
                aria-controls="collapseSixdd">
                <i class="fas fa-fw fa-cog"></i>
                <span>{{ __('Configuración') }}</span>
            </a>
            <div id="collapseSixdd"
                class="collapse sub-item-settings {{ Route::is('parameterslogs.index') ? 'show' : null }} {{ Route::is('nuevo.usuario') ? 'show': null }} {{ Route::is('nuevo.cliente') ? 'show': null }} {{ Route::is('roles.create') ? 'show': null }} {{ Route::is('clientes.index') ? 'show' : null }} {{ Route::is('create.provider') ? 'show' : null }} {{ Route::is('proveedores.index') ? 'show' : null }} {{ Route::is('importer') ? 'show' : null }}">
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ Route::is('nuevo.cliente') ? 'item-settings-active' : null }} {{ Route::is('clientes.index') ? 'item-settings-active' : null }}" href="#" data-toggle="collapse" data-target="#collapseClient" aria-expanded="true"
                    aria-controls="collapseClient" id="collapseClient" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Clientes') }} </a>

                    <div id="collapseClient" class="collapse {{ active_class(Route::is('clientes.index')) }}" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="nav-item">
                            <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('clientes.index')) }} {{ seleted_option(Route::is('clientes.index')) }}" href="{{ route('clientes.index') }}" id="configuracion_cliente" style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"> <i class="fas fa-angle-right px-2"></i><span>{{ __('Configuración') }}</span></a>
                        </div>
                    </div>

                </div>
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ Route::is('proveedores.index') ? 'item-settings-active' : null }}" href="#" data-toggle="collapse" data-target="#collapseProvider" aria-expanded="true"
                    aria-controls="collapseProvider" id="collapseProvider" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Proveedores') }} </a>
                    <div id="collapseProvider" class="collapse {{ active_class(Route::is('proveedores.index')) }}" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="nav-item">
                            <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('proveedores.index')) }} {{ seleted_option(Route::is('proveedores.index')) }}" href="{{ route('proveedores.index') }}" id="proveedor_index" style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"><i class="fas fa-angle-right px-2"></i><span>{{ __('Configuración') }}</span></a>
                        </div>
                    </div>
                </div>
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ Route::is('users.group') ? 'item-settings-active': null }}" href="#" data-toggle="collapse" data-target="#collapseProviderSixTen" aria-expanded="true"
                    aria-controls="collapseProviderSixTen" id="collapseProviderSixTen" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Usuarios') }} </a>
                    <div id="collapseProviderSixTen" class="collapse {{ active_class(Route::is('users.group')) }}" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="nav-item">
                            <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('users.group')) }} {{ seleted_option(Route::is('users.group')) }}" href="{{ route('users.group') }}" id="usuarios" style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"><i class="fas fa-angle-right px-2"></i><span>{{ __('Consultas') }}</span></a>
                        </div>
                    </div>
                </div>

                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('importer')) }} {{ seleted_option(Route::is('importer')) }}" href="{{ route('importer') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem" id="import_datos">{{ __('Importar datos') }} </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link collapsed nav-seeker {{ Route::is('nuevo.usuario') ? 'item-settings-active': null }}"
                        data-toggle="collapse" data-target="#collapseSettingsNew" aria-expanded="true"
                        aria-controls="collapseSettingsNew"
                        style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">
                        {{__('Nuevos')}}
                    </a>
                    <div id="collapseSettingsNew" class="collapse {{ active_class(Route::is('nuevo.usuario')) }} {{ active_class(Route::is('roles.create')) }} {{ active_class(Route::is('nuevo.cliente')) }} {{ active_class(Route::is('create.provider')) }}" aria-labelledby="headingTwo" data-parent="#accordionSidebar">>
                        <div class="nav-item">
                            <a href="{{ route('nuevo.usuario') }}"
                                style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"
                                class="nav-link collapsed nav-seeker {{ active_class(Route::is('nuevo.usuario')) }} {{ seleted_option(Route::is('nuevo.usuario')) }}">
                                {{__('Usuarios')}}
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="{{ route('roles.create') }}"
                                style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"
                                class="nav-link collapsed nav-seeker {{ active_class(Route::is('roles.create')) }} {{ seleted_option(Route::is('roles.create')) }}"
                                >
                                {{__('Roles')}}
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="{{ route('nuevo.cliente') }}"
                                style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"
                                class="nav-link collapsed nav-seeker {{ active_class(Route::is('nuevo.cliente')) }} {{ seleted_option(Route::is('nuevo.cliente')) }}"
                                >
                                {{__('Clientes')}}
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="{{ route('create.provider') }}"
                                style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"
                                class="nav-link collapsed nav-seeker {{ active_class(Route::is('create.provider')) }} {{ seleted_option(Route::is('create.provider')) }}"
                                >
                                {{__('Proveedores')}}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-item">
                    <a href="#" class="nav-link collapsed nav-seeker {{ Route::is('parameterslogs.index') ? 'item-settings-active': null }}"
                        data-toggle="collapse" data-target="#collapseParametersLogs" aria-expanded="true"
                        aria-controls="collapseParametersLogs"
                        style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">
                        {{__('Logs')}}
                    </a>
                    <div id="collapseParametersLogs" class="collapse {{ active_class(Route::is('parameterslogs.index')) }}"  aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="nav-item">
                            <a href="{{ route('parameterslogs.index') }}"
                                style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"
                                class="nav-link collapsed nav-seeker
                                {{ active_class(Route::is('parameterslogs.index')) }}
                                {{ seleted_option(Route::is('parameterslogs.index')) }}">
                                {{__('Parametrización')}}
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </li>
    @endif
    <hr class="sidebar-divider my-0">
    @if (in_array(Auth::user()->rol_id, [1, 14]))
        <li id="settings-items-gestion" class="settings-items nav-item {{ Route::is('budgets.settings') ? 'show': null }} {{ Route::is('metrics') ? 'show': null }} {{ Route::is('order.logs') ? 'show': null }} {{ Route::is('budget.logs') ? 'show': null }}">
            <a class="nav-link collapsed nav-seeker" href="#" data-toggle="collapse" data-target="#collapseTen" aria-expanded="true"
                aria-controls="collapseTen">
                <i class="fas fa-fw fa-cog"></i>
                <span>{{ __('Gestion') }}</span>
            </a>
            <div id="collapseTen" class="collapse sub-item-settings {{ Route::is('budgets.settings') ? 'show' : null }} {{ Route::is('nuevo.usuario') ? 'show': null }} {{ Route::is('users.group') ? 'show': null }} {{ Route::is('metrics') ? 'show': null }} {{ Route::is('order.logs') ? 'show': null }}">
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('budgets.settings')) }} {{ seleted_option(Route::is('budgets.settings')) }}" href="{{ route('budgets.settings') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem"> {{ __('Presupuestos') }} </a>
                </div>
                @if (Auth::user()->rol_id == 1)
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('metrics')) }} {{ seleted_option(Route::is('metrics')) }}" href="{{ route('metrics') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem"> {{ __('Metricas de Proveedores') }} </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link nav-seeker collapsed {{ Route::is('order.logs') ? 'item-settings-active': null }} {{ Route::is('budget.logs') ? 'item-settings-active': null }}" href="#" data-toggle="collapse" data-target="#collapseLogs" aria-expanded="true"
                    aria-controls="collapseLogs" id="collapseLogs" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Logs') }} </a>
                    <div id="collapseLogs" class="collapse {{ active_class(Route::is('order.logs')) }} {{ active_class(Route::is('budget.logs')) }}" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="nav-item">
                            <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('order.logs')) }} {{ seleted_option(Route::is('order.logs')) }}" href="{{ route('order.logs') }}" id="orderLogs" style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"><i class="fas fa-angle-right px-2"></i><span>{{ __('Pedidos') }}</span></a>
                        </div>
                        <div class="nav-item">
                            <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('budget.logs')) }} {{ seleted_option(Route::is('budget.logs')) }}" href="{{ route('budget.logs') }}" id="budgetLog" style="margin-left: -2rem;padding-left: 3rem; font-size: 0.85rem"><i class="fas fa-angle-right px-2"></i><span>{{ __('Presupuesto') }}</span></a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </li>

    @endif
    <hr class="sidebar-divider my-0">
    @if (in_array(Auth::user()->rol_id, [1, 14]))
        <li class="settings-items nav-item">
            <a class="nav-link collapsed nav-seeker" href="#" data-toggle="collapse" data-target="#collapseFiveTen" aria-expanded="true"
                aria-controls="collapseFiveTen">
                <i class="fas fa-fw fa-cog"></i>
                <span>{{ __('Parametrización') }}</span>
            </a>
            <div id="collapseFiveTen" class="collapse sub-item-settings {{ Route::is('parametersroles.index') ? 'show' : null }} {{ Route::is('parametersnotifications.index') ? 'show' : null }}">
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('parametersroles.index')) }} {{ seleted_option(Route::is('parametersroles.index')) }}" href="{{ route('parametersroles.index') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem"> {{ __('Rol') }} </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('parametersnotifications.index')) }} {{ seleted_option(Route::is('parametersnotifications.index')) }}" href="{{ route('parametersnotifications.index') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem"> {{ __('Notificaciones') }} </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('parametersemailorder')) }} {{ seleted_option(Route::is('parametersemailorder')) }}" href="{{ route('parametersemailorder') }}" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem"> {{ __('Reporte Diario') }} </a>
                </div>
            </div>
        </li>

    @endif

    <hr class="sidebar-divider my-0">
     <li class="nav-item {{ active_class(Route::is('wiki')) }} {{ seleted_option(Route::is('wiki')) }}">
        <a class="nav-link nav-seeker" href="{{ route('wiki') }}">
            <i class="fas fa-fw fa-question-circle"></i>
            <span>{{ __('Ayuda') }}</span></a>
    </li>
    <hr class="sidebar-divider my-0">
    <li class="settings-items nav-item {{ Route::is('faq.responsables') ? 'show' : null }} {{ Route::is('faq.index') ? 'show' : null }} {{ Route::is('faq.proveedor') ? 'show' : null }} {{ Route::is('faq.taller') ? 'show' : null }}">
            <a class="nav-link collapsed nav-seeker" href="#" data-toggle="collapse" data-target="#collapseSeven" aria-expanded="true"
                aria-controls="collapseSeven">
                <i class="fas fa-fw fa-question"></i>
                <span>{{ __('FAQ') }}</span>
            </a>
            <div id="collapseSeven" class="collapse {{ active_class(Route::is('faq.responsables')) }} {{ active_class(Route::is('faq.index')) }} {{ active_class(Route::is('faq.proveedor')) }} {{ active_class(Route::is('faq.taller')) }}">
                @if (in_array(Auth::user()->rol_id, [12]))
                    <div class="nav-item">
                        <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('faq.index')) }}" href="{{ route('faq.index') }} {{ seleted_option(Route::is('faq.index')) }}"
                        id="faq_responsable_compra" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem"> {{ __('Responsable De Compras') }}</a>
                    </div>
                @elseif(in_array(Auth::user()->rol_id, [1]))
                    <div class="nav-item">
                        <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('faq.responsables')) }} {{ seleted_option(Route::is('faq.responsables')) }}" href="{{ route('faq.responsables') }}" id="faq_adm_responsable_compra" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Responsable De Compras') }} </a>
                    </div>
                @endif
                @if(in_array(Auth::user()->rol_id, [8]))
                     <div class="nav-item">
                        <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('faq.index')) }} {{ seleted_option(Route::is('faq.index')) }}" href="{{ route('faq.index') }}" id="faq_proveedor" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Proveedor') }}</a>
                    </div>
                @elseif(in_array(Auth::user()->rol_id, [1]))
                    <div class="nav-item">
                        <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('faq.proveedor')) }} {{ seleted_option(Route::is('faq.proveedor')) }}" href="{{ route('faq.proveedor') }}" id="faq_adm_proveedor" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Proveedor') }}</a>
                    </div>
                @endif
               @if (in_array(Auth::user()->rol_id, [9]))
                <div class="nav-item">
                    <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('faq.index')) }} {{ seleted_option(Route::is('faq.index')) }}" href="{{ route('faq.index') }}" id="faq_taller" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Taller') }}</a>
                </div>
                @elseif(in_array(Auth::user()->rol_id, [1]))
                    <div class="nav-item">
                        <a class="nav-link collapsed nav-seeker {{ active_class(Route::is('faq.taller')) }} {{ seleted_option(Route::is('faq.taller')) }}" href="{{ route('faq.taller') }}" id="faq_adm_taller" style="margin-left: -1rem;padding-left: 2.5rem; font-size: 0.85rem">{{ __('Taller') }}</a>
                    </div>
               @endif
            </div>
    </li>
    <hr class="sidebar-divider my-0">
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>
@endauth
