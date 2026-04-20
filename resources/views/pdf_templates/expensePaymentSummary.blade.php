<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

    @include('pdf_templates.partials.header')
    @include('pdf_templates.partials.watermark')

    <div class="container" style="padding: 60px">
        <div class="row">
            <div class="container">
                <div class="col-lg-12">
                    <h1 style="text-transform: uppercase;"><img src="{{ public_path("media/{$setting->logo}") }}" alt="Sign here" style="width: 100%; height: auto;"></h1>
                    <h3 style="text-align:center">EXPENSE PAYMENT SUMMARY</h3>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                    <table class="table">
                        <thead>
                            <th style="width:70%;"></th>
                            <th style="width:70%;"></th>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="width:70%;"><strong>Grand Total:</strong> K {{ number_format(collect($expensePayments)->flatten()->sum('amount'), 2) }}</td>
                                <td style="width:70%;">
                                    <strong>Period Generated:</strong>
                                    @if($startDate && $endDate)
                                        {{ \Carbon\Carbon::parse($startDate)->format('j F, Y') }}
                                        to
                                        {{ \Carbon\Carbon::parse($endDate)->format('j F, Y') }}
                                    @else
                                        {{ ucfirst($period) }}
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>

                <table class="table table-striped table-responsive" style="font-size:12px; background-color: #ffffff">
                    <thead style="color: #ffffff !important; background-color:#0665d0;">
                        <th class="invoice-td" style="text-align:left">Date</th>
                        <th class="invoice-td" style="text-align:left">Student</th>
                        <th class="invoice-td" style="text-align:left">Expense Type</th>
                        <th class="invoice-td" style="text-align:left">Amount Paid</th>
                    </thead>
                    <tbody>
                        @foreach ($expensePayments as $payment)
                            <tr>
                                <td class="invoice-td">
                                    <b>{{ $payment->created_at->format('j F, Y') }}</b>
                                    {{ $payment->created_at->format('H:i:s') }}
                                </td>

                                <td class="invoice-td">
                                    @if($payment->student)
                                        {{ $payment->student->fname }}
                                        {{ $payment->student->mname }}
                                        {{ $payment->student->sname }}
                                    @endif
                                </td>

                                <td class="invoice-td">
                                    {{ $payment->expense->name ?? '-' }}
                                </td>

                                <td class="invoice-td">
                                    {{ number_format($payment->amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

</body>
</html>
