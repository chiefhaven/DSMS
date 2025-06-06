@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
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
        @foreach ($instructors as $instructor)
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <!-- Profile Row (Avatar + Info) -->
                        <div class="row align-items-center mb-3">
                            <!-- Avatar Column -->
                            <div class="col-auto">
                                <img class="rounded-circle"
                                    src="{{ $instructor->avatar_url ?? 'media/avatars/avatar6.jpg' }}"
                                    alt="{{ $instructor->fname }}'s avatar"
                                    width="80"
                                    height="80">
                            </div>

                            <!-- Info Column -->
                            <div class="col ps-0">
                                <h5 class="mb-1">{{ $instructor->fname }} {{ $instructor->sname }}</h5>
                                <ul class="list-unstyled text-muted small mb-0">
                                    <li class="mb-1">
                                        <i class="fas fa-phone-alt me-1"></i>
                                        {{ $instructor->phone ?? 'N/A' }}
                                    </li>
                                    <li class="mb-1">
                                        <i class="fas fa-envelope me-1"></i>
                                        {{ $instructor->user->email ?? 'N/A' }}
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <i class="fas fa-user-tag me-1"></i>
                                        <span class="badge bg-{{ $instructor->status === 'Active' ? 'success' : 'danger' }}">
                                            {{ $instructor->status }}
                                        </span>
                                        @if($instructor->department)
                                            <span class="badge bg-info ms-1 text-capitalize">
                                                {{ $instructor->department->name }}
                                            </span>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Assignments Section -->
                        <div class="border-top pt-3 mt-3">
                            <h6 class="text-uppercase fs-sm text-muted mb-2">Assignments</h6>

                            @if($instructor->fleet || $instructor->classrooms->isNotEmpty())
                                <ul class="list-unstyled small">
                                    @if($instructor->fleet)
                                        <li class="mb-2">
                                            <i class="fas fa-car me-1"></i>
                                            {{ $instructor->fleet->car_registration_number }}
                                            ({{ $instructor->fleet->car_brand_model }})
                                        </li>
                                    @endif

                                    @if($instructor->classrooms->isNotEmpty())
                                        <li>
                                            <i class="fas fa-chalkboard-teacher me-1"></i>
                                            @foreach($instructor->classrooms as $classroom)
                                                <span class="d-block">{{ $classroom->name }} - {{ $classroom->location }}</span>
                                            @endforeach
                                        </li>
                                    @endif
                                </ul>
                            @else
                                <p class="text-muted small mb-0">No assignments</p>
                            @endif
                        </div>

                        <!-- Action Button at Bottom -->
                        <div class="d-grid mt-4">
                            <div class="dropdown">
                                <button class="btn btn-outline-dark dropdown-toggle w-100"
                                        type="button"
                                        id="instructorActionsDropdown"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fas fa-user-tie me-2"></i> Manage Instructor
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="instructorActionsDropdown">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center"
                                           href="{{ route('viewinstructor', $instructor->id) }}">
                                            <i class="fas fa-eye me-2"></i> View Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center"
                                           href="{{ route('editinstructor', $instructor->id) }}">
                                            <i class="fas fa-edit me-2"></i> Edit Profile
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ url('/deleteinstructor', $instructor->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item d-flex align-items-center"
                                                    onclick="return confirm('Are you sure you want to delete {{ $instructor->fname }} {{ $instructor->sname }}?')"
                                                    type="submit">
                                                <i class="fas fa-trash-alt me-2"></i> Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
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

            // Updated for SweetAlert2
            Swal.fire({
                title: `Are you sure you want to delete instructor?`,
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
