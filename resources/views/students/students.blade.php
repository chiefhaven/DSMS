@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Students</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @role(['superAdmin', 'admin'])
                <ol class="breadcrumb">
                    <a href="{{ url('/addstudent') }}" class="btn btn-primary">
                        <i class="fa fa-fw fa-user-plus mr-1"></i> Add student
                    </a>
                </ol>
            @endcan
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <script>
      @if($message = session('succes_message'))
      Swal.fire(
        'Good job!',
        '{{ $message }}',
        'success'
      )
      @endif
    </script>
    <div class="block block-rounded block-bordered">
          <div class="block-content">
            <div class="col-md-12 mb-1">
                <form action="{{ url('/search-student') }}" method="GET" enctype="multipart/form-data">
                    @csrf
                        <input type="text" class="col-md-5 block block-rounded block-bordered p-2" id="search" name="search" placeholder="Search student" required>
                        <button type="submit" class="block-rounded  p-2 btn btn-alt-primary">
                            <i class="fa fa-search opacity-50 me-1"></i> Search
                        </button>
                </form>
            </div>
            </div>
                <div class="table-responsive">
                @if( !$student->isEmpty())
                  <table class="table table-bordered table-striped table-vcenter">
                      <thead>
                          <tr>
                              <th>Name</th>
                              <th>Phone</th>
                              <th>Email</th>
                              <th>TRN</th>
                              <th>Course Enrolled</th>
                              @role('superAdmin')
                                <th>Balance</th>
                                @endrole
                              <th>Status</th>
                              <th class="text-center" style="width: 100px;">Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                        @foreach ($student as $students)
                          <tr>
                              <td class="font-w600">
                                  {{$students->fname}} {{$students->mname}} {{$students->sname}}
                              </td>
                              <td>
                                  {{$students->phone}}
                              </td>
                              <td>
                                @if(isset($students->user->email))

                                  {{$students->user->email}}

                                @else

                                @endif
                              </td>
                              <td>{{$students->trn}}</td>
                              <td>
                                @if(isset($students->course->name))

                                  {{$students->course->name}}<br>
                                  {{$students->course->short_description}}

                                @else
                                    @role(['superAdmin', 'admin'])
                                        <a href="{{ url('/addinvoice', $students->id) }}">Enroll Course</a>
                                    @else
                                        <strong>Not enrolled yet.</strong>
                                        <br class="muted sm-text"><small>Ask administrator to enroll the student</small>
                                    @endrole
                                @endif
                              </td>
                              @role('superAdmin')
                                <td>
                                    <strong>
                                    @if(isset($students->invoice->invoice_balance))
                                        @if (number_format($students->invoice->invoice_balance) > 0)
                                            <span class="text-danger">
                                                K{{number_format($students->invoice->invoice_balance, 2)}}
                                            </span>
                                        @else
                                            <span class="text-success">
                                                K{{number_format($students->invoice->invoice_balance, 2)}}
                                            </span>
                                        @endif

                                    @else
                                            -
                                    @endif
                                    </strong>
                                </td>
                                @endrole
                              <td class="text-center">
                                @if(isset($students->course->duration))
                                    @if(number_format($students->attendance->count()/$students->course->duration*100) >= 100)
                                        <span class="badge rounded-pill bg-success">Finished</span>
                                    @elseif(number_format($students->attendance->count()/$students->course->duration*100) >= 50 && number_format($students->attendance->count()/$students->course->duration*100) !== 100)
                                        <div class="push">
                                            <span class="badge rounded-pill bg-info text-light">
                                                    {{number_format($students->attendance->count()/$students->course->duration*100)}}%
                                            </span>
                                        </div>
                                    @else
                                        <span class="badge rounded-pill bg-warning text-dark">
                                            {{number_format($students->attendance->count()/$students->course->duration*100)}}%
                                        </span>
                                    @endif
                                @else
                                    <span class="badge rounded-pill bg-warning text-dark">
                                        0%
                                    </span>
                                @endif
                              </td>
                              <td class="text-center">
                                <div class="dropdown d-inline-block">
                                  <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="d-sm-inline-block">Action</span>
                                  </button>
                                  <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                      <a class="dropdown-item nav-main-link" href="{{ url('/viewstudent', $students->id) }}">
                                        <i class="nav-main-link-icon fa fa-user"></i>Profile
                                      </a>
                                    @role(['superAdmin', 'admin'])
                                        <form method="POST" class="dropdown-item nav-main-link" action="{{ url('/edit-student', $students->id) }}">
                                            {{ csrf_field() }}
                                            <i class="nav-main-link-icon fa fa-pencil"></i>
                                            <button class="btn" type="submit">Edit</button>
                                        </form>
                                        @role(['superAdmin'])
                                        <form class="dropdown-item nav-main-link" method="POST" action="{{ url('student-delete', $students->id) }}">
                                            {{ csrf_field() }}
                                            {{ method_field('DELETE') }}
                                            <i class="nav-main-link-icon fa fa-trash"></i>
                                            <button class="btn delete-confirm" type="submit">Delete</button>
                                        </form>
                                        @endcan
                                        <form method="POST" class="dropdown-item nav-main-link" action="{{ url('send-notification', $students->id) }}">
                                            {{ csrf_field() }}
                                            <i class="nav-main-link-icon fa fa-paper-plane"></i>
                                            <button class="btn" type="submit">Send balance reminder</button>
                                        </form>
                                    @endcan
                                    </div>
                                  </div>
                                </div>
                              </td>
                          </tr>
                          @endforeach

                      </tbody>
                  </table>
                    {{ $student->links('pagination::bootstrap-4') }}
                @else
                    <p class="p-5">No matching records found!</p>
                @endif
                </div>
          </div>
      </div>
    </div>
  <!-- END Hero -->

  <script type="text/javascript">
        $('.delete-confirm').on('click', function (e) {
            e.preventDefault();
            var form = $(this).parents('form');
            swal({
                title: 'Delete student',
                text: 'Are you sure you want to delete student',
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
