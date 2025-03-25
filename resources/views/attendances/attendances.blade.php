@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Attendances</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
            <ol class="breadcrumb">
            @role(['instructor'])
            <div class="col-md-12 block-rounded block-bordered p-4 dropdown d-inline-block">
                <form action="{{ url('/attendanceSummary') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <b>Download Summary</b> &nbsp;
                    <select class="btn btn-light" id="period" name="period" onchange="this.form.submit()">
                        <option value="">Choose date...</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="thisweek">This Week</option>
                        <option value="thismonth">This Month</option>
                        <option value="lastmonth">Last Month</option>
                        <option value="thisyear">This Year</option>
                        <option value="lastyear">Last Year</option>
                        <option value="alltime">All Time</option>
                    </select>
                </form>

            </div>
            @endcan
            </ol>
        </nav>
        </div>
    </div>
    </div>

  <div class="content content-full">
    @if ($errors->any())
        <script>
            swal.fire({
                title: "{{ __('Success!') }}",
                text: "{{ session('toast_success') }}",
                type: "success"
            });
        </script>
    @endif
    @include('components.alert')
    <div class="block block-rounded block-bordered">
          <div class="block-content">
                <div class="table-responsive">
                  <table id="attendances" class="table table-bordered table-striped table-vcenter">
                      <thead>
                          <tr>
                            <th class="text-center" >Actions</th>
                            <th style="min-width: 50px;">Date</th>
                            <th>Student</th>
                            <th style="width: 20%;">Lesson</th>
                            @role(['superAdmin', 'admin'])
                                <th style="width: 20%;">Instructor</th>
                            @endcan
                          </tr>
                      </thead>
                      <tbody>
                        @foreach ($attendance as $attend)
                          <tr>
                            <td class="text-center">
                                <div class="dropdown d-inline-block">
                                    <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="d-none d-sm-inline-block">Action</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                        <button class="dropdown-item" type="submit" disabled>View</button>
                                        @role(['superAdmin'])
                                            <form method="POST" action="{{ url('/editattendance', $attend->id) }}">
                                                {{ csrf_field() }}
                                                <button class="dropdown-item" type="submit">Edit</button>
                                            </form>
                                            <form method="POST" action="{{ url('/deleteattendance', $attend->id) }}">
                                                {{ csrf_field() }}
                                                {{ method_field('DELETE') }}
                                                <button class="dropdown-item" onclick="return confirm('Are you sure you want to delete attendance?')" type="submit">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                    </div>
                                </div>
                            </td>
                              <td class="font-w600">
                                  {{$attend->attendance_date->format('j F, Y, H:i:s')}}
                              </td>
                              <td>
                                  {{$attend->student->fname}} <strong>{{$attend->student->sname}}</strong>
                              </td>
                              <td>
                                  {{$attend->lesson->name}}
                              </td>
                              @role(['superAdmin', 'admin'])
                                <td>
                                    @if(isset($attend->instructor))
                                        {{$attend->instructor->fname}}
                                        <strong>{{$attend->instructor->sname}}</strong>
                                    @endif
                                    -
                                </td>
                              @endcan
                          </tr>
                          @endforeach
                      </tbody>
                  </table>
                </div>
          </div>
      </div>
    </div>

    <!-- Attendance Report Modal -->
    <div class="modal" id="summary" tabindex="-1" aria-labelledby="summary" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-themed block-transparent mb-0">
            <div class="block-header bg-primary-dark">
                <h3 class="block-title">Download Report</h3>
                <div class="block-options">
                <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa fa-fw fa-times"></i>
                </button>
                </div>
            </div>
            <div class="block-content">
                <form class="mb-5" action="{{ url('/downloadSummary') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                    @csrf
                <div class="row haven-floating">
                    <div class="col-7 form-floating mb-4">

                        <label for="district">Period</label>
                    </div>
                </div>
            </form>
            </div>
        </div>
        </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $.extend($.fn.dataTable.ext.type.order, {
                "custom-date-pre": function (data) {
                    // Parse '9 May, 2024, 02:00:00' format
                    const parts = data.split(/[\s,]+/);
                    const day = parseInt(parts[0], 10);
                    const month = [
                        "January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"
                    ].indexOf(parts[1]);
                    const year = parseInt(parts[2], 10);
                    const timeParts = parts[3].split(':');
                    const hours = parseInt(timeParts[0], 10);
                    const minutes = parseInt(timeParts[1], 10);
                    const seconds = parseInt(timeParts[2], 10);

                    return new Date(year, month, day, hours, minutes, seconds).getTime();
                }
            });


            $('#attendances').DataTable({
                order: [[1, 'desc']], // Sort by Date column (0 index)
                columnDefs: [
                    { targets: 1, type: 'custom-date' }, // Apply custom date sorting
                    { targets: 0, orderable: false },
                ]
            });
        });
    </script>
<!-- END Hero -->
@endsection
