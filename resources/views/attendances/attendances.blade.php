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
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-none d-sm-inline-block"><i class="fa fa-download"></i> Summary</span>
                    </button>
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
                  <table class="table table-bordered table-striped table-vcenter">
                      <thead>
                          <tr>
                            @role(['superAdmin', 'admin'])
                                <th class="text-center" >Actions</th>
                            @endcan
                            <th style="min-width: 100px;">Date</th>
                            <th>Student</th>
                            <th style="width: 20%;">Lesson</th>
                          </tr>
                      </thead>
                      <tbody>
                        @foreach ($attendance as $attend)
                          <tr>
                            @role(['superAdmin', 'admin'])
                            <td class="text-center">
                                <div class="dropdown d-inline-block">
                                    <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="d-none d-sm-inline-block">Action</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                        <form method="POST" action="{{ url('/editattendance', $attend->id) }}">
                                        {{ csrf_field() }}
                                        <button class="dropdown-item" type="submit">Edit</button>
                                        </form>
                                        <form method="POST" action="{{ url('/deleteattendance', $attend->id) }}">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="dropdown-item" onclick="return confirm('Are you sure you want to delete attendance?')" type="submit">Delete</button>
                                        </form>
                                    </div>
                                    </div>
                                </div>
                            </td>
                            @endcan
                              <td class="font-w600">
                                  {{$attend->attendance_date->format('j F, Y, H:i:s' )}}
                              </td>
                              <td>
                                  {{$attend->student->fname}} {{$attend->student->sname}}
                              </td>
                              <td>
                                  {{$attend->lesson->name}}
                              </td>
                          </tr>
                          @endforeach
                      </tbody>
                  </table>
                  {{ $attendance->links('pagination::bootstrap-4') }}
                </div>
          </div>
      </div>
    </div>

    <!-- Attendance Report Modal -->
    <div class="modal" id="modal-block-vcenter" tabindex="-1" aria-labelledby="modal-block-vcenter" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-themed block-transparent mb-0">
            <div class="block-header bg-primary-dark">
                <h3 class="block-title">Add Payment</h3>
                <div class="block-options">
                <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa fa-fw fa-times"></i>
                </button>
                </div>
            </div>
            <div class="block-content">
                <form class="mb-5" action="{{ url('/add-payment') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                    @csrf
                    @if(isset($student->invoice->created_at))
                        <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{$student->invoice->invoice_number}}" hidden>
                    @else

                    @endif
                <div class="col-12 form-floating mb-4">
                    <input type="date" class="form-control" id="date_created" name="date_created" placeholder="Enter invoice date">
                    <label for="invoice_discount">Date</label>
                </div>
                <div class="row">
                    <div class="col-6 form-floating mb-4">
                        <input type="number" class="form-control" id="paid_amount" name="paid_amount" value="0">
                        <label for="invoice_discount">Amount</label>
                    </div>
                    <div class="col-6 form-floating mb-4">
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="Cash" selected>Cash</option>
                            <option value="National Bank">National Bank</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="TNM Mpamba">TNM Mpamba</option>
                            <option value="Other">Other</option>
                        </select>
                        <label for="district">Payment Method</label>
                    </div>
                </div>
                <div class="col-12 form-floating mb-4">
                    <input type="file" class="form-control" id="payment_proof" name="payment_proof" placeholder="Upload a reciept">
                    <label for="invoice_discount">Payment proof</label>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
            </div>
        </div>
        </div>
        </div>
    </div>
<!-- END Hero -->
@endsection
