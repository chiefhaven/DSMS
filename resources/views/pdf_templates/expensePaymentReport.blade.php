<!DOCTYPE html>
<html lang="en">
<head>
    @include('pdf_templates.css')
</head>
<body>

    @include('pdf_templates.partials.header')
    @include('pdf_templates.partials.watermark')
    @include('pdf_templates.partials.pdf_template_style')

    <!-- QR Code (adjust position as needed) -->
    <div style="position: absolute; top: 130px; right: 30px; height: 150px; width: 200px;">
        <img src="data:image/png;base64, {!! $qrCode !!}" height="70" width="70" alt="QR Code">
    </div>

    <div class="container-fluid" style="margin-top: 100px;">
        <h3 style="text-transform: uppercase; text-align: center;">
            STUDENTS EXPENSE PAYMENT REPORT FOR GROUP {{ $expense->group }}
        </h3>

        <p class="text-muted" style="font-size: 14px; text-align: center;">
            Expected payout: K{{ number_format($expense->amount * $expense->students->count(), 2) }}
            | Actual: K{{ number_format($expense->students->sum('pivot.amount'), 2) }}
        </p>

        <div class="bg-body" style="z-index: 999;">
            <table class="table table-striped table-responsive" style="font-size: 10px; width: 100%; border-collapse: collapse;">
                <thead style="color: #ffffff; background-color: #0665d0; text-align: left;">
                    <tr>
                        <th>Student</th>
                        <th class="invoice-td">Expense Type</th>
                        <th class="invoice-td">Expected</th>
                        <th class="invoice-td">Paid</th>
                        <th class="invoice-td">Paid By</th>
                        <th class="invoice-td">Date Paid</th>
                        <th class="invoice-td">Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expense->students as $student)
                        <tr>
                            <td class="invoice-td text-uppercase">
                                {{ $student->fname }} {{ $student->mname }} <strong>{{ $student->sname }}</strong>
                            </td>
                            <td class="invoice-td text-center">
                                {{ $expenseTypeNames[$student->pivot->expense_type] ?? 'Unknown' }}
                            </td>
                            <td class="invoice-td text-center">
                                K{{ number_format($expense->amount ?? 0, 2) }}
                            </td>
                            <td class="invoice-td text-center">
                                {{ ($student->pivot->amount ?? 0) > 0 ? 'K'.number_format($student->pivot->amount) : 'Not Paid' }}
                            </td>
                            <td class="invoice-td text-center">
                                {{
                                    optional(
                                        $enteredByAdmins[$student->pivot->payment_entered_by] ?? null
                                    )->administrator->fname ?? '-'
                                }}
                            </td>
                            <td class="invoice-td text-center">
                                {{ $student->pivot->paid_at
                                    ? \Carbon\Carbon::parse($student->pivot->paid_at)->format('d M, Y')
                                    : '-'
                                }}
                            </td>
                            <td class="invoice-td text-center">
                                {{ $student->pivot->payment_method ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
