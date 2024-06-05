@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Students</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
              {{Session::get('message')}}
            </div>
          @endif

          @role(['superAdmin', 'admin'])
            <div class="dropdown d-inline-block">
                <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="d-sm-inline-block">Action</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0">
                  <div class="p-2">
                  @role(['superAdmin', 'admin'])
                        <a href="{{ url('/addstudent') }}" class="dropdown-item nav-main-link">
                            <i class="fa fa-fw fa-user-plus mr-1"></i>&nbsp; Add student
                        </a>
                        <button class="dropdown-item nav-main-link" data-bs-toggle="modal" data-bs-target="#modal-block-vcenter">
                            <i class="fa fa-download"></i> &nbsp; Students report
                        </button>
                  @endcan
                  </div>
                </div>
              </div>
            @endcan
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block block-rounded block-bordered">
          <div class="block-content">
            <div class="col-md-12 mb-1">
                <form action="{{ url('/search-student') }}" method="GET" enctype="multipart/form-data">
                    @csrf
                        <input type="text" class="col-md-5 block block-bordered p-2" id="search" name="search" placeholder="Search student" required>
                        <button type="submit" class="p-2 btn btn-alt-primary">
                            <i class="fa fa-search opacity-50 me-1"></i> Search
                        </button>
                </form>
            </div>
            </div>
                <div class="m-4 table-responsive">
                @if( !$student->isEmpty())
                  <table class="table table-bordered table-striped table-vcenter">
                      <thead class="thead-dark">
                          <tr>

                            <th class="text-center" style="width: 100px;">Actions</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>TRN</th>
                            <th style="min-width: 10rem;">Registered on</th>
                            <th style="min-width: 15rem;">Course Enrolled</th>
                            @role(['superAdmin','admin'])
                                <th style="min-width: 10rem;">Car assigned</th>
                            @endrole
                            @role('superAdmin')
                            <th>Balance</th>
                            @endrole
                            <th>Status</th>
                          </tr>
                      </thead>
                      <tbody>
                        @foreach ($student as $students)
                            <tr>
                                <td class="text-center">
                                    <div class="dropdown d-inline-block">
                                        <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="d-sm-inline-block">Action</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-0">
                                        <div class="p-2">
                                            <a class="dropdown-item nav-main-link" href="{{ url('/viewstudent', $students->id) }}">
                                            <i class="nav-main-link-icon fa fa-user"></i><div class="btn">Profile</div>
                                            </a>
                                        @role(['instructor'])
                                            <form class="dropdown-item nav-main-link" method="POST" action="{{ url('addattendance', $students->id) }}">
                                                {{ csrf_field() }}
                                                <i class="nav-main-link-icon fa fa-clock"></i>
                                                <button class="btn" type="submit">Enter Attendance</button>
                                            </form>
                                        @endcan
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
                                <td>{{$students->created_at->format('j F, Y')}}</td>
                                <td>
                                @if(isset($students->course->name))

                                <strong>{{$students->course->name}}</strong><br>
                                    {{$students->course->short_description}}

                                @else
                                    @role(['superAdmin'])
                                        <a href="{{ url('/addinvoice', $students->id) }}">Enroll Course</a>
                                    @else
                                        <strong class="text-danger">Not enrolled yet.</strong>
                                        <br><small class="muted sm-text text-warning">Ask the authorities to enroll the student</small>
                                    @endrole
                                @endif
                                </td>
                                @role(['superAdmin'])
                                    <td>
                                        {{$students->fleet->car_brand_model}} - <small>{{$students->fleet->car_registration_number}}</small>

                                    </td>
                                @endrole
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
                                        <span class="badge rounded-pill bg-success">Completed</span>
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

    @role(['superAdmin', 'admin'])
    <!-- Payment Modal -->
    <div class="modal" id="modal-block-vcenter" tabindex="-1" aria-labelledby="modal-block-vcenter" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-themed block-transparent mb-0">
            <div class="block-header bg-primary-dark">
                <h3 class="block-title">Filter to download report</h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form class="mb-5" action="{{ url('/studentsPdf') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                    @csrf
                    <div class="row">
                        <div class="col-sm-6 mb-4">
                            <label for="invoice_discount">Date</label>
                            <select class="form-select dropdown-toggle" id="date" name="date">
                                <div class="dropdown-menu">
                                    <option class="" value="all_time">All Time</option>
                                </div>
                            </select>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <label for="balance">Balance</label>
                            <select class="form-control dropdown-toggle" id="balance" name="balance">
                                <div class="dropdown-menu">
                                    <option class="" value="all">All</option>
                                    <option class="text-left" value="balance">With balance</option>
                                    <option class="" value="no_balance">No balance</option>
                                </div>
                            </select>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <label for="car">Assigned Car</label>
                            <select class="form-select" id="fleet" name="fleet">
                                <option class="" value="alltime" selected>All</option>
                                @foreach($fleet as $fleet_option)
                                  <option value="{{$fleet_option->car_registration_number}}"><strong>{{$fleet_option->car_registration_number}}</strong> <div class="text-muted text-small">({{$fleet_option->car_brand_model}})</div></option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <label for="status">Student Status</label>
                            <select class="form-control dropdown-toggle form-select" id="status" name="status">
                                <div class="dropdown-menu">
                                    <option class="" value="allstatus" selected>All</option>
                                    <option value="inprogress">In progress</option>
                                    <option value="finished">Completed</option>
                                </div>
                            </select>
                        </div>
                        <div class="block-content block-content-full text-end bg-body">
                            <button type="submit" class="btn btn-primary">Download</button>
                            <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        </div>
        </div>
    </div>
    @endcan
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
