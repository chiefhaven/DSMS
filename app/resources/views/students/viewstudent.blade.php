@extends('layouts.backend')

@section('content')
<!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{$student->fname}} {{$student->sname}}</h1>
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
      </ul>
      <div class="block-content tab-content">
        <div class="tab-pane fade active show" id="student-details" role="tabpanel" aria-labelledby="student-details-tab">
          <div class="content content-full row">
            <div class="col-6" style="background: #ffffff; margin: 0 10px; border-radius: 5px; border: thin solid #cdcdcd;">
              <div class="py-6 px-4">
                <img class="img-avatar img-avatar96 img-avatar-thumb" src="/../media/avatars/avatar2.jpg" alt="">
                <h1 class="my-2">{{$student->fname}} {{$student->sname}}</h1>
                <p>
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
                          <tr>
                              <td>
                                  Enrolled on
                              </td>
                              <td>
                                @if(isset($student->invoice->created_at))
                                  {{$student->invoice->created_at}}

                                @else
                                  <a href="{{ url('/addinvoice', $student->id) }}">Enroll Course</a>
                                @endif
                              </td>
                          </tr>
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
                                  
                                @endif
                              </td>
                          </tr>
                          
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
                        <form method="POST" action="/trafic-card-reference-letter/{{$student->id}}">
                          {{ csrf_field() }}
                          <button class="btn btn-primary" type="submit">Download</button>
                        </form>
                      </td>
                    </tr>
                    <tr>
                      <td>2</td>
                      <td>Highway code 1 Aptitude Reference Letter</td>
                      <td>
                        <form method="POST" action="/aptitude-test-reference-letter/{{$student->id}}">
                          {{ csrf_field() }}
                          <button class="btn btn-primary" type="submit">Download</button>
                        </form>
                      </td>
                    </tr>
                    <tr>
                      <td>3</td>
                      <td>Highway code 2 Aptitude Reference Letter</td>
                      <td>
                        <form method="POST" action="/second-aptitude-test-reference-letter/{{$student->id}}">
                          {{ csrf_field() }}
                          <button class="btn btn-primary" type="submit">Download</button>
                        </form>
                      </td>
                    </tr>
                    <tr>
                      <td>4</td>
                      <td>Final Reference Letter</td>
                      <td>
                        <form method="POST" action="/final-test-reference-letter/{{$student->id}}">
                          {{ csrf_field() }}
                          <button class="btn btn-primary" type="submit">Download</button>
                        </form>
                      </td>
                    </tr>
                    <tr>
                      <td>5</td>
                      <td>Lesson Attendance Report</td>
                      <td>
                        <form method="POST" action="/lesson-report/{{$student->id}}">
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
    <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
     <div class="table-responsive" style="overflow-x: inherit;">
        <table class="table table-bordered table-striped table-vcenter">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice No</th>
                    <th>Total</th>
                    <th >Due</th>
                    <th class="text-center" style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-w600">
                      @if(isset($student->invoice->created_at))
                        {{$student->invoice->date_created->format('j F, Y')}}
                      @else
                                  
                      @endif
                    </td>
                    <td class="font-w600">
                      @if(isset($student->invoice->created_at))
                        {{$student->invoice->invoice_number}}
                      @else
                                  
                      @endif
                    </td>
                    <td>
                      @if(isset($student->invoice->created_at))
                        K{{number_format($student->invoice->invoice_total)}}
                      @else
                                  
                      @endif
                    </td>
                    <td>
                      @if(isset($student->invoice->created_at))
                        {{$student->invoice->invoice_payment_due_date->format('j F, Y')}}
                      @else
                                  
                      @endif
                    </td>
                    @if(isset($student->invoice->created_at))
                    <td class="text-center">
                      <div class="dropdown d-inline-block">
                          <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="d-none d-sm-inline-block">Action</span>
                          </button>
                          <div class="dropdown-menu dropdown-menu-end p-0">
                            <div class="p-2">
                              <a class="dropdown-item" href="{{ url('/view-invoice', $student->invoice->invoice_number) }}">
                                View
                              </a>
                              <form method="POST" action="{{ url('/edit-invoice', $student->invoice->invoice_number) }}">
                                    {{ csrf_field() }}
                                    <button class="dropdown-item" type="submit">Edit</button>
                                  </form>
                              <a class="dropdown-item" href="javascript:void(0)">
                                Add payment
                              </a>
                              <a class="dropdown-item" href="javascript:void(0)">
                                Print Invoice
                              </a>
                              <form method="POST" action="{{ url('/invoice-delete', $student->invoice->id) }}">
                                {{ csrf_field() }}
                                {{ method_field('DELETE') }} 
                                <button class="dropdown-item" onclick="return confirm('Are you sure?')" type="submit">Delete</button>
                              </form>
                            </div>
                          </div>
                        </div>
                    </td>
                    @endif
                </tr>
            </tbody>
        </table>
      </div>        
    </div>
    <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-vcenter">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference number</th>
                    <th style="width: 30%;">Payment Method</th>
                    <th style="width: 15%;">Amount</th>
                    <th class="text-center" style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($student->payment as $payment)
                <tr>
                    <td class="font-w600">
                        {{$payment->created_at->format('j F, Y')}}
                    </td>
                    <td class="font-w600">
                        {{$payment->transaction_id}}
                    </td>
                    <td>Cash</td>
                    <td>
                        K{{number_format($payment->amount_paid)}}
                    </td>
                    <td class="text-center">
                        <div class="dropdown d-inline-block">
                        <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <span class="d-none d-sm-inline-block">Action</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0">
                          <div class="p-2">
                            <a class="dropdown-item" href="{{ url('#') }}">
                              View
                            </a>
                            <a class="dropdown-item" href="javascript:void(0)">
                              Edit
                            </a>
                            <a class="dropdown-item" href="javascript:void(0)">
                              Delete
                            </a>
                          </div>
                        </div>
                      </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
      </div>      
    </div>
  </div>
  </div>
</div>


@endsection
