@role(['superAdmin', 'admin'])
    <div class="tab-pane fade" id="expensesList" role="tabpanel" aria-labelledby="expensesList-tab">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-vcenter">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th style="min-width: 7em;">Status</th>
                        <th style="min-width: 7em;">Description</th>
                        <th style="min-width: 7em;">Type</th>
                        <th style="min-width: 7em;">Amount</th>
                        <th style="min-width: 7em;">Paid</th>
                        <th style="min-width: 7em;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($student->expenses as $expense)
                        <tr>
                            <td class="font-w600">
                                <a href="{{ url('/view-expense/' . $expense->id) }}"
                                class="fw-bold text-decoration-none"
                                target="_blank"
                                rel="noopener noreferrer">
                                    {{ $expense->group }}
                                </a>
                            </td>
                            <td>
                                @if ($expense->approved == '1')
                                    <div class="text-center p-1 text-success">
                                        <i class="fa fa-check" aria-hidden="true"></i> Approved
                                    </div>
                                @else
                                    <div class="text-center p-1 text-danger">
                                        <i class="fa fa-times" aria-hidden="true"></i> Unapproved
                                    </div>
                                @endif
                            </td>
                            <td class="font-w600">{{ $expense->description }}</td>
                            <td>
                                {{ \App\Http\Controllers\HavenUtils::getExpenceTypeOption($expense->pivot->expense_type) ?? 'Unknown Expense Type' }}
                            </td>
                            <td>K{{ number_format($expense->pivot->amount) }}</td>
                            <td>K{{ number_format($expense->pivot->paid) }}</td>
                            <td>K{{ number_format($expense->pivot->balance) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="fw-bold h-100 py-5">
                Expense Balance: K{{ number_format($student->expenses->sum(fn($expense) => $expense->pivot->balance ?? 0), 2) }}
            </div>
        </div>
    </div>
@endrole
