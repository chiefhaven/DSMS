@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-light my-2 my-sm-3">Enroll <strong>{{$student->fname}} {{$student->mname}} {{$student->sname}}</strong></h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
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
    <div class="block block-rounded">
    <div class="block-content">
        <div class="row">
            <div class="col-lg-12 col-xl-12">
                <form class="mb-5" action="{{ url('/store-invoice') }}" method="post" onsubmit="return true;">
                        @csrf
                        <input type="text" class="form-control" id="student" name="student" placeholder="Student" value="{{$student->id}}" hidden>

                    <div class="row">
                        <div class="col-6 form-floating mb-4">
                            <input type="text" class="form-control" id="date_created" name="date_created" placeholder="Enter invoice date" value="12/07/2022">
                            <label class="px-4" for="invoice_discount">Date</label>
                        </div>
                        <div class="col-6 form-floating mb-4">
                            <input type="text" class="form-control" id="invoice_due_date" name="invoice_due_date" placeholder="Enter invoice due date" value="12/07/2022">
                            <label class="px-4" for="invoice_discount">Invoice Due Date</label>
                        </div>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        <select class="form-select" id="course" name="course">
                          @foreach($course as $course_option)
                            <option value="{{$course_option->name}}" selected>{{$course_option->name}} (K{{$course_option->price}})</option>
                          @endforeach
                        </select>
                        <label for="district">Course To Enroll</label>
                    </div>
                    <div class="row">
                        <div class="col-4 form-floating mb-4">
                            <input type="number" class="form-control" id="discount" name="discount" placeholder="Enter discount (Fixed)" value="0">
                            <label class="px-4" for="invoice_discount">Discout</label>
                        </div>
                        <div class="col-4 form-floating mb-4">
                            <input type="number" class="form-control" id="paid_amount" name="paid_amount" placeholder="Enter discount (Fixed)" value="0">
                            <label class="px-4" for="invoice_discount">Amount Paid</label>
                        </div>
                        <div class="col-4 form-floating mb-4">
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="cash" selected>Cash</option>
                            </select>
                            <label class="px-4" for="district">Payment Method</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
  </div>
  <!-- END Hero -->
  <script>
    $(document).ready(function() {
        var today = new Date();
        var day = ("0" + today.getDate()).slice(-2); // Get day with leading zero
        var month = ("0" + (today.getMonth() + 1)).slice(-2); // Get month with leading zero (Months are zero-based)
        var year = today.getFullYear();

        // Set the datepicker
        $("#date_created").datepicker({
            format: "dd-mm-yyyy",
            autoclose: true,
            todayHighlight: true
        }).datepicker('setDate', day + '-' + month + '-' + year);

        // Set the datepicker
        $("#invoice_due_date").datepicker({
            format: "dd-mm-yyyy",
            autoclose: true,
            todayHighlight: true
        }).datepicker('setDate', day + '-' + month + '-' + year);
    });
  </script>

@endsection
