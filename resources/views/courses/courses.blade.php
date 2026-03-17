@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
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
        @foreach ($courses as $course)
        <div class="col-md-6 col-xl-3 mb-4">
            <div class="block block-rounded block-link-shadow h-100 d-flex flex-column">
                <!-- Icon/Header -->
                <div class="block-content text-center py-4">
                    <i class="fa fa-book fa-2x text-primary"></i>
                </div>

                <!-- Course Info -->
                <div class="block-content text-center border-top border-bottom pb-3">
                    <h5 class="mb-1 fw-bold">{{ $course->name }}</h5>
                    <p class="text-muted small mb-2">
                        {{ $course->short_description }}
                    </p>
                    <span class="badge bg-success me-1 m-1">
                        {{ $course->practical_count }} Practicals
                    </span>
                    <span class="badge bg-info">
                        {{ $course->theory_count }} Theories
                    </span>
                </div>

                <!-- Students Stats -->
                <div class="block-content py-3">
                    <div class="d-flex justify-content-between text-center">
                        <div class="flex-fill">
                            <small class="text-muted d-block">Active</small>
                            <span class="fw-bold text-success fs-5">
                                {{ $course->active_students_count ?? $course->student()->where('status','!=','Finished')->count() }}
                            </span>
                        </div>

                        <div class="flex-fill border-start">
                            <small class="text-muted d-block">Total</small>
                            <span class="fw-bold text-dark fs-5">
                                {{ $course->total_students_count ?? $course->student()->count() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="block-content mt-auto text-center mb-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary px-4" data-bs-toggle="dropdown">
                            Actions
                        </button>

                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <a class="dropdown-item" href="{{ url('/view-course', $course->id) }}">
                                <i class="fa fa-eye me-2 text-info"></i> View
                            </a>

                            <a class="dropdown-item" href="{{ url('/edit-course', $course->id) }}">
                                <i class="fa fa-edit me-2 text-warning"></i> Edit
                            </a>

                            <div class="dropdown-divider"></div>

                            <form method="POST" action="{{ url('/delete-course', $course->id) }}">
                                @csrf
                                @method('DELETE')
                                <button class="dropdown-item text-danger delete-confirm"
                                    onclick="return confirm('Are you sure you want to delete this course?');">
                                    <i class="fa fa-trash me-2"></i> Delete
                                </button>
                            </form>
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
