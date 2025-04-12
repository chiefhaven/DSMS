@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Invoice No. {{$invoice->invoice_number}}</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
            <a class="btn btn-primary" href="{{ url('/invoice-pdf', $invoice->id) }}">
              <i class="si si-printer me-1"></i> Print Invoice
            </a>
        </nav>
      </div>
    </div>
  </div>

  <div id="invoice" class="content content-full">
        <div class="block-content">
            <div class="row block">
                <div class="col-lg-12">
                    <div class="p-sm-4 p-xl-7">
                        <div class="row mb-5">
                          <div class="col-6">
                            <p>Invoice No. {{$invoice->invoice_number}}</p>
                            <div class="h3">Student</div>
                            <strong>{{$invoice->student->fname}} {{$invoice->student->sname}}</strong>
                            <address>
                              Street Address: {{$invoice->student->address}}<br>
                              District: {{$invoice->student->district->name}}<br>
                              Phone: {{$invoice->student->phone}}<br>
                              Email: {{ $invoice->student->user->email ?? '' }}
                            </address>
                          </div>
                          <div class="col-6 text-end">
                            <p><h3>{{$setting->school_name}}</h3>
                              {{$setting->slogan}}</p>
                            <address>
                              {{$setting->address}}<br>
                              P.O. Box {{$setting->postal}}<br>
                              {{$setting->district->name}}<br>
                              {{$setting->email}}<br>
                              {{$setting->phone_1}} | {{$setting->phone_2}}
                            </address>
                          </div>
                        </div>
                        <div class="table-responsive push">
                          <table class="table table-bordered">
                            <thead class="bg-body">
                              <tr>
                                <th class="text-center" style="width: 30px;">#</th>
                                <th>Course</th>
                                <th class="text-center" style="width: 90px;">Fees</th>
                                <th class="text-end" style="width: 120px;">Discount</th>
                                <th class="text-end" style="width: 200px;">Total Amount</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td class="text-center">1</td>
                                <td>
                                  <p class="fw-semibold mb-1">{{$invoice->course->name}}</p>
                                  <div class="text-muted">{{$invoice->course->short_description}}</div>
                                </td>
                                <td class="text-end">
                                  K{{number_format($invoice->invoice_total, 2)}}
                                </td>
                                <td class="text-end">K{{number_format($invoice->invoice_discount, 2)}}</td>
                                <td class="text-end">K{{number_format($invoice->invoice_total, 2)}}</td>
                              </tr>
                              <tr>
                                <td colspan="4" class="fw-semibold text-end">Subtotal</td>
                                <td class="text-end">K{{number_format($invoice->invoice_total, 2)}}</td>
                              </tr>
                              <tr>
                                <td colspan="4" class="fw-semibold text-end">Paid</td>
                                <td class="text-end">K{{number_format($invoice->invoice_amount_paid, 2)}}</td>
                              </tr>
                              <tr>
                                <td colspan="4" class="fw-semibold text-end">Vat Rate</td>
                                <td class="text-end">0%</td>
                              </tr>
                              <tr>
                                <td colspan="4" class="fw-semibold text-end">Vat Due</td>
                                <td class="text-end">K00.00</td>
                              </tr>
                              <tr>
                                <td colspan="4" class="fw-bold text-uppercase text-end bg-body-light">Total Due</td>
                                <td class="fw-bold text-end bg-body-light">K{{number_format($invoice->invoice_balance, 2)}}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                        <p class="text-muted text-center my-5">
                          Thank you for doing business with us.
                        </p>
                      </div>
                </div>
            </div>
        </div>
  </div>
  <script type="text/javascript">
    function printDiv(divName) {
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = printContents;

     window.print();

     document.body.innerHTML = originalContents;
}
  </script>
  <!-- END Hero -->

@endsection
