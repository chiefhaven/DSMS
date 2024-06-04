@extends('layouts.backend')

@section('content')
<!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{$student->fname}} {{$student->mname}} {{$student->sname}}</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <div class="dropdown d-inline-block">
              <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="d-none d-sm-inline-block">Action</span>
              </button>
              <div class="dropdown-menu dropdown-menu-end p-0">
                <div class="p-2">
                  <form method="POST" action="/edit-student/{{$student->id}}">
                    {{ csrf_field() }}
                    <button class="dropdown-item" type="submit">Edit profile</button>
                  </form>
                  <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modal-block-vcenter">
                      Add payment
                  </button>
                </div>
              </div>
            </div>
          </ol>
        </nav>
      </div>
    </div>
  </div>

    <div class="content content-full">
        <div class="block block-rounded">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="student-details-tab" data-bs-toggle="tab" data-bs-target="#student-details" role="tab" aria-controls="student-details" aria-selected="true">
                    Student Details
                    </button>
                </li>
            @role(['superAdmin', 'admin'])
                <li class="nav-item">
                    <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" role="tab" aria-controls="invoices" aria-selected="false">
                        Invoices
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" role="tab" aria-controls="payments" aria-selected="false">
                        Payments
                    </button>
                </li>
            @endcan
            </ul>
            <div class="block-content tab-content">
                <div class="tab-pane fade active show" id="student-details" role="tabpanel" aria-labelledby="student-details-tab">
                    <div class="content content-full row">
                        <div class="col-6" style="background: #ffffff; margin: 0 10px; border-radius: 5px; border: thin solid #cdcdcd;">
                        <div class="py-6 px-4">
                            <img class="img-avatar img-avatar96 img-avatar-thumb" src="/../media/avatars/avatar2.jpg" alt="">
                            <h1 class="my-2">{{$student->fname}} {{$student->mname}} {{$student->sname}}</h1>
                            <p>
                                Gender: {{$student->gender}}<br>
                                Address: {{$student->address}} <br>Phone: {{$student->phone}}<br>Email: {{$student->user->email}}<br>TRN: {{$student->trn}}
                            </p>
                        </div>
                        </div>
                        <div class="col-5" style="background: #ffffff; margin: 0 10px; border-radius: 5px; border: thin solid #cdcdcd;">
                        <div class="py-5 px-5">
                            <p><strong>General Information</strong></p>
                            <div class="table-responsive">
                            <table class="table table-bordered ">
                                <thead>

                                </thead>
                                <tbody>
                                    @role(['superAdmin', 'admin'])
                                        <tr>
                                            <td>
                                                Enrolled on
                                            </td>
                                            <td>
                                                @if(isset($student->invoice->created_at))
                                                {{$student->invoice->created_at->format('j F, Y')}}

                                                @else
                                                <a href="{{ url('/addinvoice', $student->id) }}">Enroll Course</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endcan
                                    <tr>
                                        <td>
                                            Course
                                        </td>
                                        <td>
                                            @if(isset($student->invoice->created_at))
                                            {{$student->course->name}}<br>{{$student->course->duration}} days
                                            @else

                                            @endif
                                        </td>
                                    </tr>

                                    @role(['superAdmin', 'admin'])
                                        <tr>
                                            <td>
                                                Fees
                                            </td>
                                            <td>
                                                @if(isset($student->invoice->created_at))
                                                K{{number_format($student->invoice->invoice_total)}}
                                                @else

                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                Paid
                                            </td>
                                            <td>
                                                @if(isset($student->invoice->created_at))
                                                K{{number_format($student->invoice->invoice_amount_paid)}}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                Balance
                                            </td>
                                            <td>
                                                @if(isset($student->invoice->created_at))
                                                K{{number_format($student->invoice->invoice_balance)}}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                Assigned car
                                            </td>
                                            <td>
                                                @if(isset($student->fleet->car_brand_model))
                                                    {{$student->fleet->car_registration_number}}
                                                    <div style="font-size: 10px">{{$student->fleet->car_brand_model}}</div>
                                                @else
                                                    Not assigned yet
                                                @endif
                                            </td>
                                        </tr>
                                    @endcan
                                </tbody>
                            </table>
                            </div>
                            <div class="mb-4">
                                <h4 class="mb-4">Overall progress</h4>
                                <div class="progress push">
                                <div class="progress-bar progress-bar-striped" role="progressbar" style="width: {{$attendancePercent}}%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">
                                    <span class="fs-sm fw-semibold">{{$attendancePercent}}%</span>
                                </div>
                                </div>
                                <div class="push">
                                <p>{{$attendanceTheoryCount}} days of Theory done, {{$attendancePracticalCount}} Practicals done</p>
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="col-11 px-5 py-6" style="background: #ffffff; margin: 50px 10px; border-radius: 5px; border: thin solid #cdcdcd;">
                        <h3>Downloads</h3>
                            <table class="table table-responsive table-striped">
                            <thead>
                                <th>#</th>
                                <th style="width:90%">Description</th>
                                <th>Action</th>
                            </thead>
                            <tbody>
                                <tr>
                                <td>1</td>
                                <td>Traffic Register Card-Reference</td>
                                <td>
                                    <form method="POST" action="{{ url('/trafic-card-reference-letter', $student->id) }}">
                                    {{ csrf_field() }}
                                    <button class="btn btn-primary" type="submit">Download</button>
                                    </form>
                                </td>
                                </tr>
                                <tr>
                                <td>2</td>
                                <td>Highway code 1 Aptitude Reference Letter</td>
                                <td>
                                    <form method="POST" action="{{ url('/aptitude-test-reference-letter', $student->id) }}">
                                    {{ csrf_field() }}
                                    <button class="btn btn-primary" type="submit">Download</button>
                                    </form>
                                </td>
                                </tr>
                                <tr>
                                <td>3</td>
                                <td>Highway code 2 Aptitude Reference Letter</td>
                                <td>
                                    <form method="POST" action="{{ url('/second-aptitude-test-reference-letter', $student->id) }}">
                                    {{ csrf_field() }}
                                    <button class="btn btn-primary" type="submit">Download</button>
                                    </form>
                                </td>
                                </tr>
                                <tr>
                                <td>4</td>
                                <td>Final Reference Letter</td>
                                <td>
                                    <form method="POST" action="{{ url('/final-test-reference-letter', $student->id) }}">
                                    {{ csrf_field() }}
                                    <button class="btn btn-primary" type="submit">Download</button>
                                    </form>
                                </td>
                                </tr>
                                <tr>
                                <td>5</td>
                                <td>Lesson Attendance Report</td>
                                <td>
                                    <form method="POST" action="{{ url('/lesson-report', $student->id) }}">
                                    {{ csrf_field() }}
                                    <button class="btn btn-primary" type="submit">Download</button>
                                    </form>
                                </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                    </div>
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
@endcan

@endsection

