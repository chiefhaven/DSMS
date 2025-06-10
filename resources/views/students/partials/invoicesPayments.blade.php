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
                    <th>Due</th>
                    <th class="text-center" style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($student->invoice->created_at))
                <tr>
                    <td class="font-w600">{{ $student->invoice->date_created->format('j F, Y') }}</td>
                    <td class="font-w600">{{ $student->invoice->invoice_number }}</td>
                    <td>K{{ number_format($student->invoice->invoice_total) }}</td>
                    <td>K{{ number_format($student->invoice->invoice_balance) }}</td>
                    <td>{{ $student->invoice->invoice_payment_due_date->format('j F, Y') }}</td>
                    <td class="text-center">
                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-primary" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-none d-sm-inline-block">Action</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0">
                                <div class="p-2">
                                    <a class="dropdown-item" href="{{ url('/view-invoice', $student->invoice->id) }}">View</a>
                                    <form method="get" action="{{ url('/edit-invoice', $student->invoice->id) }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">Edit</button>
                                    </form>
                                    @role('superAdmin')
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modal-block-vcenter">Add payment</button>
                                    @endrole
                                    <a class="dropdown-item" href="{{ url('/invoice-pdf', $student->invoice->id) }}">Print Invoice</a>
                                    @role('superAdmin')
                                    <form method="POST" action="{{ url('/invoice-delete', $student->invoice->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item delete-invoice" data-invoice-id="{{ $student->invoice->invoice_number }}" type="submit">Delete</button>
                                    </form>
                                    @endrole
                                    <form method="POST" action="{{ url('send-balance-sms', [$student->id,'Balance']) }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">Send balance reminder</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @else
                <tr>
                    <td colspan="6" class="text-center">No invoice available.</td>
                </tr>
                @endif
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
                    <td class="font-w600">{{ $payment->created_at->format('j F, Y') }}</td>
                    <td class="font-w600">{{ $payment->transaction_id }}</td>
                    <td>{{ $payment->paymentMethod->name ?? '' }}</td>
                    <td>K{{ number_format($payment->amount_paid) }}</td>
                    <td>{{ $payment->entered_by }}</td>
                    <td>
                        <img src="{{ asset('media/paymentProofs/' . $payment->payment_proof) }}" width="200px" alt="Proof of Payment">
                    </td>
                    <td class="text-center">
                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-primary" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-none d-sm-inline-block">Action</span>
                            </button>
                            @role('superAdmin')
                            <div class="dropdown-menu dropdown-menu-end p-0">
                                <div class="p-2">
                                    <form method="POST" action="{{ url('delete-payment', $payment->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item delete-confirm" data-transaction-id="{{ $payment->transaction_id }}" type="submit">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endrole
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endrole
