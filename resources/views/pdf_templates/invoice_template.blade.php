<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

<h3 style="text-align:center;">Cash Receipt #: {{$invoice->invoice_number}}</h3>
@include('pdf_templates.partials.header')
@include('pdf_templates.partials.pdf_template_style')

  <!-- Hero -->
    <div class="row block">
        <div class="col-lg-12">
            <div class="p-sm-4 p-xl-7">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>
                      
                    </th>
                    <th>
                      
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="invoice-td" style="border: solid #ffffff00; text-align:left; width: 90%;">
                      <div class="h3">Student</div>
                      <strong>{{$invoice->student->fname}} {{$invoice->student->mname}} {{$invoice->student->sname}}</strong>
                      <div>
                        Street Address: {{$invoice->student->address}}<br>
                        District: {{$invoice->student->district->name}}<br>
                        Phone: {{$invoice->student->phone}}<br>
                        Email: {{$invoice->student->user->email}}
                      </div>
                    </td>
                    <td class="text-end" style="border: solid #ffffff00;" valign="top">
                      Date:<br>
                      Due Date:
                    </td>
                    <td class="text-end" style="border: solid #ffffff00;" valign="top">
                      {{$invoice->date_created->format('j F, Y')}}<br>
                      {{$invoice->invoice_payment_due_date->format('j F, Y')}}
                    </td>
                  </tr>
                  </tbody>
                </table>

                <table class="table table-striped table-responsive" style="font-size:12px; background-color: #ffffff; text-align:left">
                        <thead style="color: #ffffff !important; background-color:#0665d0;">
                            <th>#</th>
                            <th>Course</th>
                            <th class="amount">Fees</th>
                            <th class="amount">Discount</th>
                            <th class="amount">Total Amount</th>
                        </thead>
                        <tbody>
                                <tr>
                                    <td class="invoice-td">
                                        1
                                    </td>
                                    <td class="invoice-td">
                                        <p class="fw-semibold mb-1">{{$invoice->course->name}}</p>
                                        <div class="text-muted">{{$invoice->course->short_description}}</div>
                                    </td>
                                    <td class="invoice-td amount">
                                        K{{number_format($invoice->invoice_total, 2)}}
                                    </td>
                                    <td class="invoice-td amount">
                                        K{{number_format($invoice->invoice_discount, 2)}}
                                    </td>
                                    <td class="invoice-td amount">
                                        K{{number_format($invoice->invoice_total, 2)}}
                                    </td>
                                </tr>
                                <tr>
                                  <td colspan="4" class="invoice-td fw-semibold text-end">Subtotal</td>
                                  <td class="invoice-td text-end">K{{number_format($invoice->invoice_total, 2)}}</td>
                                </tr>
                                <tr>
                                  <td colspan="4" class="invoice-td fw-semibold text-end">Paid</td>
                                  <td class="invoice-td text-end">K{{number_format($invoice->invoice_amount_paid, 2)}}</td>
                                </tr>
                                <tr>
                                  <td colspan="4" class="invoice-td fw-semibold text-end">Vat Rate</td>
                                  <td class="invoice-td text-end">0%</td>
                                </tr>
                                <tr>
                                  <td colspan="4" class="invoice-td fw-semibold text-end">Vat Due</td>
                                  <td class="invoice-td text-end">K00.00</td>
                                </tr>
                                <tr>
                                  <td colspan="4" class="invoice-td fw-bold text-uppercase text-end bg-body-light">Total Due</td>
                                  <td class="invoice-td fw-bold text-end bg-body-light">K{{number_format($invoice->invoice_balance, 2)}}</td>
                                </tr>
                        </tbody>
                </table>
                <p class="text-muted text-center my-5">
                  Thank you for doing business with us.
                </p>
            </div>
        </div>
    </div>

    @if(number_format($invoice->invoice_balance, 2) == 0.00)
      <div style="position:absolute; top:-250px; left:65%;">
        <img src="{{ public_path("/media/paid.png") }}" alt="PAID" style="width: 500px; height: auto;">
      </div>
    @else
                
    @endif
  <!-- END Hero -->
</body>
</html>