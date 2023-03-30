@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Students</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="{{ url('/addstudent') }}" class="btn btn-primary">
                    <i class="fa fa-fw fa-user-plus mr-1"></i> Add student
            </a>
          </ol>
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
                <div class="table-responsive" style="overflow-x: inherit;">
                  <table class="table table-bordered table-striped table-vcenter">
                      <thead>
                          <tr>
                              <th>Name</th>
                              <th>Phone</th>
                              <th>Email</th>
                              <th style="width: 20%;">TRN</th>
                              <th style="width: 15%;">Course Enrolled</th>
                              <th style="width: 15%;">Status</th>
                              <th class="text-center" style="width: 100px;">Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                        @foreach ($student as $students)
                          <tr>
                              <td class="font-w600">
                                  {{$students->fname}} {{$students->sname}}
                              </td>
                              <td>
                                  {{$students->phone}}
                              </td>
                              <td>
                                @if(isset($student->user->email))

                                  {{$student->user->email}}
    
                                @else
                                  
                                @endif
                              </td>
                              <td>{{$students->trn}}</td>
                              <td>
                                @if(isset($students->course->name))

                                  {{$students->course->name}}<br>
                                  {{$students->course->short_description}}

                                @else
                                  <a href="{{ url('/addinvoice', $students->id) }}">Enroll Course</a>
                                @endif
                              </td>
                              <td>
                                  {{$students->status}}
                              </td>
                              <td class="text-center">
                                <div class="dropdown d-inline-block">
                                  <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="d-none d-sm-inline-block">Action</span>
                                  </button>
                                  <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                      <a class="dropdown-item" href="{{ url('/viewstudent', $students->id) }}">
                                        Profile
                                      </a>
                                      <form method="POST" action="{{ url('/edit-student', $students->id) }}">
                                        {{ csrf_field() }}
                                        <button class="dropdown-item" type="submit">Edit</button>
                                      </form>
                                      <form method="POST" action="{{ url('student-delete', $students->id) }}">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }} 
                                        <button class="dropdown-item" onclick="return confirm('Are you sure you want to delete {{$students->fname}} {{$students->sname}}?')" type="submit">Delete</button>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                              </td>
                          </tr>
                          @endforeach
                          
                      </tbody>
                  </table>
                  {{ $student->links('pagination::bootstrap-4') }}
                </div>
          </div>
      </div>
    </div>
  <!-- END Hero -->

@endsection
