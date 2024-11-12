@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Instructors</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="{{ url('/addinstructor') }}" class="btn btn-primary">
                    <i class="fa fa-fw fa-user-plus mr-1"></i> Add Instructor
            </a>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
          <div class="block-content">
          @if(Session::has('message'))
            <div class="alert alert-success">
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

      <div class="row">
        @foreach ($instructor as $instructor)
        <div class="col-md-6 col-xl-4">
            <div class="block block-rounded block-link-shadow text-center" href="javascript:void(0)">
                <div class="block-content block-content-full">
                    <div class="row">
                        <div class="col-md-8">
                            <img class="img-avatar" src="media/avatars/avatar6.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="d-sm-inline-block">Action</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                    <form method="GET" action="{{ url('/editinstructor', $instructor->id) }}">
                                        {{ csrf_field() }}
                                        <button class="dropdown-item" type="submit">Edit</button>
                                    </form>
                                    <form method="POST" action="{{ url('/deleteinstructor', $instructor->id) }}">
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

                <div class="block-content block-content-full block-content-sm bg-body-light">
                    <h3 class="font-w600 mb-0">{{$instructor->fname}} {{$instructor->sname}}</h3>
                </div>
                <div class="block-content block-content-full">
                    <div class="row">
                        <div class="col-12">
                            <p class="text-muted mb-0" style="font-size: 10px;">
                                Phone: {{$instructor->phone}}<br>
                                Email: @if(isset($instructor->user->email))
                                    {{$instructor->user->email}}
                                @else
                                @endif
                                <br>
                            </p>
                            <p class="text-muted mb-0" style="font-size: 11px;">
                                <b>Car assigned</b><br>
                                @if(isset($instructor->fleet->car_registration_number))
                                    {{$instructor->fleet->car_registration_number}} -
                                    {{$instructor->fleet->car_brand_model}}

                                @else
                                    -
                                @endif
                            </p>
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
    $(document).ready(function() {
        $('.delete-confirm').on('click', function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            var instructorName = "{{ $instructor->fname }} {{ $instructor->sname }}";

            // Updated for SweetAlert2
            Swal.fire({
                title: `Are you sure you want to delete ${instructorName}?`,
                text: "All lessons belonging to this instructor will be transferred to the Super instructor.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Delete!",
                cancelButtonText: "Cancel",
                dangerMode: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

@endsection
