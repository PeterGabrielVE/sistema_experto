@extends('layouts.app', [
    'namePage' => 'Horarios',
    'class' => 'sidebar-mini',
    'activePage' => 'patients',
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
              <a class="btn btn-primary btn-round text-white pull-right" href="{{ route('schedule.create') }}">{{ __('Agregar horario') }}</a>
            <h4 class="card-title">{{ __('Gestion de horarios') }}</h4>
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
                  <th>{{ __('#') }}</th>
                  <th>{{ __('Desayuno') }}</th>
                  <th>{{ __('Almuerzo') }}</th>
                  <th>{{ __('Cena') }}</th>
                  <th>{{ __('Nota') }}</th>
                  <th>{{ __('Acciones') }}</th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                  <th>{{ __('#') }}</th>
                  <th>{{ __('Desayuno') }}</th>
                  <th>{{ __('Almuerzo') }}</th>
                  <th>{{ __('Cena') }}</th>
                  <th>{{ __('Nota') }}</th>
                  <th>{{ __('Acciones') }}</th>
                </tr>
              </tfoot>
              <tbody>
                @foreach($schedules as $sc)
                <tr>
                  <td>{{$sc->id}}</td>
                  <td>{{$sc->breakfast}}</td>
                  <td>{{$sc->lunch}}</td>
                  <td>{{$sc->dinner}}</td>
                  <td>{{$sc->notes}}</td>
                    <td class="text-right">
                      <a type="button" href="{{route("schedule.edit",$sc->id)}}" rel="tooltip" class="btn btn-success btn-icon btn-sm " data-original-title="" title="">
                        <i class="now-ui-icons ui-2_settings-90"></i>
                      </a>
                    <form action="{{ route('schedule.destroy', $sc->id) }}" method="post" style="display:inline-block;" class ="delete-form">
                      @csrf
                      @method('delete')
                      <button type="button" rel="tooltip" class="btn btn-danger btn-icon btn-sm delete-button" data-original-title="" title="" onclick="confirm('{{ __('??Est?? seguro de que desea eliminar este horario?') }}') ? this.parentElement.submit() : ''">
                        <i class="now-ui-icons ui-1_simple-remove"></i>
                      </button>
                    </form>
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

