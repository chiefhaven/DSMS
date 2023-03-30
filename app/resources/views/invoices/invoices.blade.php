@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Invoices</h1>
      </div>
    </div>
  </div>

  <div class="content content-full">
    @if(Session::has('message'))
      <div class="alert alert-info">
        {{Session::get('message')}}
      </div>
    @endif
    <div class="block block-rounded block-bordered">
          <div class="block-content">
            <div class="table-responsive" style="overflow-x: inherit;">
              <table class="table table-bordered table-striped table-vcenter">
                  <thead>
                      <tr>
                          <th>Date</th>
                          <th>Invoice No</th>
                          <th>Student</th>
                          <th style="width: 10%;">Course Price</th>
                          <th style="width: 10%;">Discount</th>
                          <th style="width: 10%;">Total</th>
                          <th style="width: 10%;">Paid</th>
                          <th style="width: 15%;">Balance</th>
                          <th>Due</th>
                          <th class="text-center" style="width: 100px;">Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                    @foreach ($invoices as $invoice)
                      <tr>
                          <td class="font-w600">
                              {{$invoice->date_created->format('j F, Y')}}
                          </td>
                          <td class="font-w600">
                              <a href="{{ url('/view-invoice/', $invoice->invoice_number) }}">{{$invoice->invoice_number}}</a>
                          </td>
                          <td>
                              {{$invoice->student->fname}} {{$invoice->student->sname}}
                          </td>
                          <td>
                            K{{number_format($invoice->course_price, 2)}}
                          </td>
                          <td>
                            K{{number_format($invoice->invoice_discount, 2)}}
                          </td>
                          <td>
                            K{{number_format($invoice->invoice_total, 2)}}
                          </td>
                          <td>
                            K{{number_format($invoice->invoice_amount_paid, 2)}}
                          </td>
                          <td>
                              K{{number_format($invoice->invoice_balance, 2)}}
                          </td>
                          <td>
                              {{$invoice->invoice_payment_due_date->format('j F, Y')}}
                          </td>
                          <td class="text-center">
                              <div class="dropdown d-inline-block">
                              <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-none d-sm-inline-block">Action</span>
                              </button>
                              <div class="dropdown-menu dropdown-menu-end p-0">
                                <div class="p-2">
                                  <a class="dropdown-item" href="{{ url('/view-invoice', $invoice->invoice_number) }}">
                                    View
                                  </a>
                                  <form method="POST" action="{{ url('/edit-invoice', $invoice->invoice_number) }}">
                                        {{ csrf_field() }}
                                        <button class="dropdown-item" type="submit">Edit</button>
                                      </form>
                                  <a class="dropdown-item" href="javascript:void(0)">
                                    Add payment
                                  </a>
                                  <a class="dropdown-item" href="javascript:void(0)">
                                    Print Invoice
                                  </a>
                                  <form method="POST" action="{{ url('/invoice-delete', $invoice->id) }}">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }} 
                                        <button class="dropdown-item" onclick="return confirm('Are you sure?')" type="submit">Delete</button>
                                      </form>
                                </div>
                              </div>
                            </div>
                          </td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
              {{ $invoices->links('pagination::bootstrap-4') }}
            </div>
          </div>
      </div>
    </div>
  <!-- END Hero -->

@endsection
