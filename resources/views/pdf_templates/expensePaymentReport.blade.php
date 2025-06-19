<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

@include('pdf_templates.partials.header')
@include('pdf_templates.partials.watermark')
@include('pdf_templates.partials.pdf_template_style')


<div class="" style="position:absolute; bottom:75%; right: -5%; height: 150px; width:200px;">
    <img src="data:image/png;base64, {!! $qrCode !!} " height="70" width="70">
</div>

<div class="container-fluid" style="margin-top: 100px;">
    <h3 style="text-transform: uppercase;">
        STUDENTS EXPENSE PAYMENT REPORT FOR GROUP {{ $expense->group }}
    </h3>
    <p class="text-muted" style="font-size: 14px;">
        Expected payout: K{{ number_format($expense->amount * $expense->students->count(), 2) }}
        | Actual: K{{ number_format($expense->students->sum('pivot.amount'), 2) }}
    </p>

    <div class="bg-body" style="z-index:999 !important">
        <table class="table table-striped table-responsive" style="font-size:12px; with: 100%;">
            <thead style="color: #ffffff !important; background-color:#0665d0; text-align:left !important">
                <th class="invoice-td" style="text-align:left !important">Student</th>
                <th class="invoice-td">Expense type</th>
                <th class="invoice-td">Amount</th>
                <th class="invoice-td">Status</th>
                <th class="invoice-td">Paid by</th>
                <th class="invoice-td">Date paid</th>
                <th class="invoice-td">Payment method</th>
            </thead>
            <tbody>
                @foreach ($expense->students as $student)
                    <tr class="py-1">
                        <td class="invoice-td text-uppercase">
                            {{ $student->fname }} {{ $student->mname }} <strong>{{ $student->sname }}</strong>
                        </td>
                        <td class="invoice-td text-center">
                            {{ $student->pivot->expense_type ?? '-' }}
                        </td>
                        <td class="invoice-td text-center">
                            K{{ number_format($student->pivot->amount ?? 0, 2) }}
                        </td>
                        <td class="invoice-td text-center">
                            {{ ($student->pivot->amount ?? 0) > 0 ? 'Paid' : 'Not Paid' }}
                        </td>
                        <td class="invoice-td text-center">
                            {{
                                optional(
                                    $enteredByAdmins[$student->pivot->payment_entered_by] ?? null
                                )->administrator->fname ?? '-'
                            }}
                        </td>
                        <td class="invoice-td text-center">
                            {{ $student->pivot->paid_at ? \Carbon\Carbon::parse($student->pivot->paid_at)->format('d M, Y') : '-' }}
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