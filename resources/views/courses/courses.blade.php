@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Courses</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="/addcourse" class="btn btn-primary">
                    <i class="fa fa-fw fa-plus mr-1"></i> Add Course
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
        @foreach ($course as $course)
        <div class="col-md-6 col-xl-3">
            <div class="block block-rounded block-link-shadow text-center">
                <div class="block-content block-content-full p-5">
                    <i class="fa fa-fw fa-book fa-2xl text-large"></i>
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    <p class="font-w600 mb-0">{{$course->name}}</p>
                    <p class="font-size-sm font-italic text-muted mb-0">
                        {{$course->short_description}}
                    </p>
                    <p class="font-size-sm font-italic text-muted mb-0">
                        {{$course->practicals}} days practicals plus {{$course->theory}} days theory.
                    </p>
                </div>
                <div class="block-content block-content-full overflow-visible">
                    <div class="row gutters-tiny">
                        <div class="col-10">
                            <p class="mb-2">

                            </p>
                            <p class="font-size-sm text-muted mb-0">
                                <b>{{$course->invoice->count()}}</b> Students all time enrolled
                            </p>
                        </div>
                        <div class="col-2">
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-clear" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0">
                                <div class="p-2">
                                    <a class="dropdown-item" href="{{ url('/view-course', $course->id) }}">
                                    View
                                    </a>
                                    <form method="POST" action="{{ url('/edit-course', $course->id) }}">
                                    {{ csrf_field() }}
                                    <button class="dropdown-item" type="submit">Edit</button>
                                    </form>
                                    <form method="POST" action="{{ url('/delete-course', $course->id) }}">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <button class="dropdown-item delete-confirm" onclick="return confirm('Are you sure you want to delete this course?');" type="submit">Delete</button>
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

@endsection

<script type="text/javascript">
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).parents('form');
        swal({
            title: 'Delete course',
            text: 'Are you sure you want to delete course',
            icon: 'warning',
            buttons: ["Cancel", "Yes!"],
        }).then(function(isConfirm){
                if(isConfirm){
                        form.submit();
                }
        });
    });

</script>
