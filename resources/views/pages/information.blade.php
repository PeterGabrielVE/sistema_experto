@extends('layouts.app', [
  'namePage' => 'Información',
  'class' => 'sidebar-mini',
  'activePage' => 'information',
])

@section('content')
  <div class="panel-header">
    <div class="header text-center">
      <h2 class="title">Información</h2>
    </div>
  </div>
  <div class="content">
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">Insulina</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <span>La insulina es una hormona que permite que el azúcar en la sangre, conocido como glucosa, pase a las células. La glucosa proviene de los alimentos y las bebidas que consume. Es la principal fuente de energía del cuerpo. La insulina juega un papel clave en el mantenimiento de niveles correctos de glucosa en la sangre.</span>
            </div>
           
          </div>
          <div class="card-header">
            <h4 class="card-title">IMC (Estado nutricional del paciente)</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-success">
              <span>Según la OMS el IMC es un sencillo índice sobre la relación entre el peso y la altura, generalmente utilizado para clasificar el peso insuficiente, el peso excesivo y la obesidad en los adultos.
                Se calcula dividiendo el peso en kilogramos por el cuadrado de la altura en metros (kg/m2). </br>
            IMC = peso (kg) / altura (m) x altura (m).</br> Los rangos en los que se ubica se pueden apreciar en la siguiente imagen: </span>
            </div>
            <div class="text-center">
                <img src="{{ asset('assets/img/imc.png') }}" alt=""  width="350" height="250">
            </div>
           
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">Resistencia a la Insulina</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-primary">
              <span>La resistencia a la insulina es un trastorno metabólico que provoca que las células del cuerpo no respondan normalmente a la insulina. La glucosa no puede ingresar a las células con la misma facilidad, lo que provoca que se acumule en la sangre. Los rangos en donde se ubica la resistencia a la insulina es entre los 100 a 125. En la siguiente imagen de forma gráfica como este trastorno se manifiesta en el organismo.
                </span>
                
            </div>
            <div class="text-center">
                <img src="{{ asset('assets/img/resistencia.png') }}" alt=""  width="500" height="250">
            </div>
            
          </div>
        </div>
      </div>
      
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <div class="places-buttons">
              <div class="row">
                <div class="col-md-6 ml-auto mr-auto text-center">
                  <h4 class="card-title">
                  Método Pulgar
                  </h4>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-8 ml-auto mr-auto">
                  <div class="row">
                    <div class="col-md-12 btn-primary">
                    Este procedimiento permite determinar cuál es el consumo calórico diario que se debe tener para mantener las funciones de cuerpo desempeñándose de forma correcta. Este se calcula de la siguiente manera: 
                    </br>  Método pulgar = Peso*Factor de corrección 
                    </div>
                    <br>
                    <div class="mt-4 col-md-6 btn-primary">
                        <span><b>Factor de corrección</b></br>
                        Este es el IMC deseado que el paciente debe tener para mantener los niveles de insulina bajo control. Este se determina según el estado nutricional del paciente en base a ello se establece cuáles son las necesidades que este tiene y que IMC debe tener para estar saludable.
                        </span>
                    </div>
                    <div class="mt-4 col-md-5 btn-primary ml-1">
                        <span><b>Tratamientos farmacológicos utilizado</b></br>
                        ESi bien la resistencia a la insulina está netamente asociada con los hábitos que tenga el paciente y modificándolo se puede controlar, de forma simultanea se prescriben fármacos para tratar esta condición. Los más utilizados son: 
                        <ul>
                            <li>Metformina</li>
                            <li>Glitazonas</li>
                            <li>Exenatida y liraglutide</li>
                        </ul>
                        </span>
                    </div>
                    
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection