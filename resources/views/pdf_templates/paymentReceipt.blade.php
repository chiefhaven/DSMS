<!DOCTYPE html>
<html>
<head>
    <title>Expense Payment Receipt</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            max-width: 60mm; /* thermal roll width, adjust if needed */
            margin: 0 auto;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .receipt-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        p {
            margin: 2px 0;
        }
        hr {
            border: none;
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="receipt-title">EXPENSE PAYMENT RECEIPT</div>
    </div>

    <p><strong>Student:</strong> {{ optional($payment->student)->fname }} {{ optional($payment->student)->sname }}</p>
    <p><strong>Group:</strong> {{ optional($payment->expense)->group }}</p>
    <p><strong>Expense:</strong> {{ $payment->expense_type }}</p>
    <p><strong>Amount:</strong> K{{ number_format($payment->amount, 2) }}</p>
    <p><strong>Method:</strong> {{ $payment->payment_method }}</p>
    <p><strong>Paid By:</strong> {{ optional($payment->paymentUser->administrator)->fname }} {{ optional($payment->paymentUser->administrator)->sname }}</p>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($payment->paid_at)->format('j M, Y H:i') }}</p>

    <hr>
    <p class="text-center">Thank you for choosing DARON!</p>
</body>
</html>
