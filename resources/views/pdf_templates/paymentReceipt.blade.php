<!DOCTYPE html>
<html>
<head>
    <title>Expense Payment Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1 class="text-center">Expense Payment Receipt</h1>
    <p><strong>Student:</strong> {{ optional($payment->student)->fname }} {{ optional($payment->student)->sname }}</p>
    <p><strong>Group:</strong> {{ optional($payment->expense)->group }}</p>
    <p><strong>Expense Type:</strong> {{ $payment->expense_type }}</p>
    <p><strong>Amount Paid:</strong> K{{ number_format($payment->amount, 2) }}</p>
    <p><strong>Payment Method:</strong> {{ $payment->payment_method }}</p>
    <p><strong>Paid By:</strong> {{ optional($payment->paymentUser->administrator)->fname }} {{ optional($payment->paymentUser->administrator)->sname }}</p>
    <p><strong>Date Paid:</strong> {{ \Carbon\Carbon::parse($payment->paid_at)->format('j F, Y') }}</p>

    <hr>
    <p class="text-center">Thank you for choosing DARON!</p>
</body>
</html>
