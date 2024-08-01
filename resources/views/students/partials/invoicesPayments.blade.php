@role(['superAdmin', 'admin'])
        <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
        <div class="table-responsive" style="overflow-x: inherit;">
            <table class="table table-bordered table-striped table-vcenter">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice No</th>
                        <th>Total</th>
                        <th>Balance</th>
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
                                K{{number_format($student->invoice->invoice_balance)}}
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
                                    @role(['superAdmin'])
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modal-block-vcenter">
                                            Add payment
                                        </button>
                                    @endcan
                                    <a class="dropdown-item" href="{{ url('/invoice-pdf', $student->invoice->invoice_number) }}">
                                        Print Invoice
                                    </a>
                                    <form method="POST" action="{{ url('/invoice-delete', $student->invoice->id) }}">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="dropdown-item" onclick="return confirm('Are you sure?')" type="submit">Delete</button>
                                    </form>
                                    <form method="POST" action="{{ url('send-notification', $student->id) }}">
                                        {{ csrf_field() }}
                                        <button class="dropdown-item" type="submit">Send balance reminder</button>
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
        {{ $student->id }}
        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-vcenter">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference number</th>
                        <th style="width: 20%;">Payment Method</th>
                        <th style="width: 15%;">Amount</th>
                        <th style="width: 15%;">Entered By</th>
                        <th style="width: 15%;">Payment Proof</th>
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
                        <td>@if(isset($payment->paymentMethod->name))
                                {{$payment->paymentMethod->name}}</td>
                            @else

                            @endif
                        <td>
                            K{{number_format($payment->amount_paid)}}
                        </td>
                        <td>
                            {{$payment->entered_by}}
                        </td>
                        <td>
                            <img src="/../media/paymentProof/{{$payment->payment_proof}}"  width="200px" alt="img proof of payment"/>
                        </td>
                        <td class="text-center">
                            <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-none d-sm-inline-block">Action</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0">
                                <div class="p-2">
                                <form method="POST" action="{{ url('delete-payment', $payment->id) }}">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <button class="dropdown-item" onclick="return confirm('Are you sure you want to delete payment number {{$payment->transaction_id}}?')" type="submit">Delete</button>
                                </form>
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
    @endrole
