@role($profileInvoiceRoles)
    <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-vcenter">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 100px;">Actions</th>
                        <th>Date</th>
                        <th>Invoice No</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($student->invoice->created_at))
                    <tr>
                        <td class="text-center">
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Action
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                        <a class="dropdown-item" href="{{ url('/view-invoice', $student->invoice->id) }}">View</a>
                                        @can('edit invoices')
                                        <form method="get" action="{{ url('/edit-invoice', $student->invoice->id) }}">
                                            @csrf
                                            <button class="dropdown-item" type="submit">Edit</button>
                                        </form>
                                        @endcan
                                        @can('record fee payments')
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#paymentModal">Add payment</button>
                                        @endcan
                                        @can('download invoices')
                                        <a class="dropdown-item" href="{{ url('/invoice-pdf', $student->invoice->id) }}">Print Invoice</a>
                                        @endcan
                                        @can('delete invoices')
                                        <form method="POST" action="{{ url('/invoice-delete', $student->invoice->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item delete-invoice" data-invoice-id="{{ $student->invoice->invoice_number }}" type="submit">Delete</button>
                                        </form>
                                        @endcan
                                        @can('manage administration')
                                        <form method="POST" action="{{ url('send-balance-sms', [$student->id,'Balance']) }}">
                                            @csrf
                                            <button class="dropdown-item" type="submit">Send balance reminder</button>
                                        </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="font-w600">{{ $student->invoice->date_created->format('j F, Y') }}</td>
                        <td class="font-w600">{{ $student->invoice->invoice_number }}</td>
                        <td>K{{ number_format($student->invoice->invoice_total) }}</td>
                        <td>K{{ number_format($student->invoice->invoice_balance) }}</td>
                        <td>{{ $student->invoice->invoice_payment_due_date->format('j F, Y') }}</td>
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
@endrole

@role($profilePaymentRoles)
    <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-vcenter">
                <thead>
                    <tr>
                        <th class="text-center" style="min-width: 7em;">Actions</th>
                        <th style="min-width: 7em;">Date</th>
                        <th>Reference number</th>
                        <th style="min-width: 7em;">Payment Method</th>
                        <th style="min-width: 7em;">Amount</th>
                        <th style="min-width: 7em;">Entered By</th>
                        <th style="min-width: 7em;">Payment Proof</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($student->payment as $payment)
                    <tr>
                        <td class="text-center">
                            @can('delete fee payments')
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Action
                                </button>
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
                            </div>
                            @endcan
                        </td>
                        <td class="font-w600">{{ $payment->created_at->format('j F, Y') }}</td>
                        <td class="font-w600">{{ $payment->transaction_id }}</td>
                        <td>{{ $payment->paymentMethod->name ?? '' }}</td>
                        <td>K{{ number_format($payment->amount_paid) }}</td>
                        <td>{{ $payment->entered_by }}</td>
                        <td class="text-center align-middle">
                            @if($payment->payment_proof)
                                @php
                                    $proofPath = 'media/paymentProof/' . $payment->payment_proof;
                                    $fullPath = public_path($proofPath);
                                @endphp

                                @if(file_exists($fullPath))
                                    <div class="d-inline-block" style="max-width: 200px;">
                                        <a href="{{ asset($proofPath) }}"
                                        target="_blank"
                                        class="image-preview d-block"
                                        data-bs-toggle="tooltip"
                                        title="View full size">
                                            <img src="{{ asset($proofPath) }}"
                                                width="200"
                                                height="50"
                                                style="width: 100%; height: auto; max-height: 50px;"
                                                alt="Payment Proof"
                                                loading="lazy"
                                                onerror="this.onerror=null;this.src='{{ asset('images/default-proof.png') }}'">
                                        </a>
                                    </div>
                                @else
                                    <span class="badge bg-warning text-dark">No proof uploaded</span>
                                @endif
                            @else
                                <span class="badge bg-secondary">No proof</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endrole
