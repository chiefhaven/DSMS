@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Lessons</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="/addlesson" class="btn btn-primary">
                    <i class="fa fa-fw fa-plus mr-1"></i> Add Lesson
            </a>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    @if(Session::has('message'))
      <div class="alert alert-info">
        {{Session::get('message')}}
      </div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger">
          <ul>
              @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
              @endforeach
          </ul>
      </div>
    @endif
      <div class="block-content">
      <div class="row">
        @foreach ($lessons as $lesson)
        <div class="col-md-6 col-xl-3">
            <div class="block block-rounded block-link-shadow text-center">
                <div class="block-content block-content-full p-5">
                    <i class="fa fa-fw fa-book fa-2xl text-large"></i>
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    <p class="font-w600 mb-0">{{$lesson->name}}</p>
                </div>
                <div class="block-content block-content-full overflow-visible">
                    <div class="row gutters-tiny">
                        <div class="col-10">
                            <p class="mb-2">
                                <p class="font-size-sm font-italic text-muted mb-0">
                                    {{$lesson->description}}
                                </p>
                            </p>
                        </div>
                        <div class="col-2 col-md-2">
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-clear" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0">
                                <div class="p-2">
                                    <a class="dropdown-item" href="{{ url('/view-lesson', $lesson->id) }}">
                                    View
                                    </a>
                                    <form method="POST" action="{{ url('/edit-lesson', $lesson->id) }}">
                                    {{ csrf_field() }}
                                    <button class="dropdown-item" type="submit">Edit</button>
                                    </form>
                                    <form method="POST" action="{{ url('/delete-lesson', $lesson->id) }}">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <button class="dropdown-item delete-confirm" type="submit">Delete</button>
                                    </form>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
      </div>

      </div>
    </div>
  <!-- END Hero -->
  <script type="text/javascript">
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).parents('form');
        swal({
            title: 'Delete lesson',
            text: 'Are you sure you want to delete lesson',
            icon: 'warning',
            buttons: ["Cancel", "Yes!"],
        }).then(function(isConfirm){
                if(isConfirm){
                        form.submit();
                }
        });
    });

</script>

@endsection
