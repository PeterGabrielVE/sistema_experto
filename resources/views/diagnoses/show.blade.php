@extends('layouts.app', [
    'namePage' => 'Diagnosticos',
    'class' => 'sidebar-mini',
    'activePage' => 'diagnoses.all',
    'activeNav' => '',
])

@section('content')
  <div class="panel-header">
  </div>
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
              <a class="btn btn-primary btn-round text-white pull-right" href="{{ route('patient.create') }}">{{ __('Agregar paciente') }}</a>
            <h4 class="card-title">{{ __('Diagnosticos') }}</h4>
            <div class="col-12 mt-2">
              @include('alerts.success')
              @include('alerts.errors')
            </div>
          </div>
          <div class="card-body">
            <div class="toolbar">
              <!--        Here you can write extra buttons/actions for the toolbar              -->
            </div>
            <table id="datatable" class="table table-striped table-bordered" cellspacing="0" width="100%">
              <thead>
                <tr>
                  <th>{{ __('Perfil') }}</th>
                  <th>{{ __('Nombre Completo') }}</th>
                  <th>{{ __('Dirección') }}</th>
                  <th>{{ __('Fecha Nacimiento') }}</th>
                  <th class="disabled-sorting text-right">{{ __('Acciones') }}</th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                  <th>{{ __('Perfil') }}</th>
                  <th>{{ __('Nombre Completo') }}</th>
                  <th>{{ __('Dirección') }}</th>
                  <th>{{ __('Fecha Nacimiento') }}</th>
                  <th class="disabled-sorting text-right">{{ __('Acciones') }}</th>
                </tr>
              </tfoot>
              <tbody>
                @foreach($diagnoses as $patient)
                  <tr>
                    <td>
                      <span class="avatar avatar-sm rounded-circle">
                        <img src="{{asset('assets')}}/img/default-avatar.png" alt="" style="max-width: 80px; border-radiu: 100px">
                      </span>
                    </td>
                    <td>{{$patient->first_name ??  null }} {{$patient->last_name ??  null }}</td>
                    <td>{{$patient->address}}</td>
                    <td>{{ date('d-m-Y', strtotime($patient->created_at))  ?? null }}</td>
                      <td class="text-right">
                      <a href="{{ route('result', $patient->id) }}" class="btn btn-info btn-sm"><i class="fa fa-eye"></i>Ver</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <!-- end content-->
        </div>
        <!--  end card  -->
      </div>
      <!-- end col-md-12 -->
    </div>
    <!-- end row -->
  </div>
@endsection

@push('js')
  <script>
    $(document).ready(function() {
      $(".delete-button").click(function(){ 
        var clickedButton = $( this );
        Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn btn-success',
        cancelButtonClass: 'btn btn-danger',
        confirmButtonText: 'Yes, delete it!',
        buttonsStyling: false
      }).then((result) => {
        if (result.value) {
          clickedButton.parents(".delete-form").submit();
        }
      })

      })
      $('#datatable').DataTable({
        "pagingType": "full_numbers",
        "lengthMenu": [
          [10, 25, 50, -1],
          [10, 25, 50, "All"]
        ],
        responsive: true,
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Search records",
        }

      });

      var table = $('#datatable').DataTable();

      // Edit record
      table.on('click', '.edit', function() {
        $tr = $(this).closest('tr');
        if ($($tr).hasClass('child')) {
          $tr = $tr.prev('.parent');
        }

        var data = table.row($tr).data();
        alert('You press on Row: ' + data[0] + ' ' + data[1] + ' ' + data[2] + '\'s row.');
      });

      // Delete a record
      table.on('click', '.remove', function(e) {
        $tr = $(this).closest('tr');
        if ($($tr).hasClass('child')) {
          $tr = $tr.prev('.parent');
        }
        table.row($tr).remove().draw();
        e.preventDefault();
      });

      //Like record
      table.on('click', '.like', function() {
        alert('You clicked on Like button');
      });
    });
  </script>
@endpush