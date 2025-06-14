<!DOCTYPE html>
<html>
<head>
    <title>Expense Payment Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .container {
            border: 2px solid #000;
            padding: 20px;
            max-width: 400px;
            position: relative;
        }
        .logo {
            width: 120px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .stamp {
            position: absolute;
            right: 20px;
            bottom: 20px;
            width: 100px;
            opacity: 0.5; /* makes it look stamped */
        }
    </style>
</head>
<body>
    <div class="container">
        @include('pdf_templates.partials.header')

        <div class="text-center">
            <img src="{{ public_path('media/{$setting->logo}') }}" class="logo" alt="Logo">
            <h2>Expense Payment Receipt</h2>
        </div>

        <p><strong>Student:</strong> {{ optional($payment->student)->fname }} {{ optional($payment->student)->sname }}</p>
        <p><strong>Group:</strong> {{ optional($payment->expense)->group }}</p>
        <p><strong>Expense Type:</strong> {{ $payment->expense_type }}</p>
        <p><strong>Amount Paid:</strong> K{{ number_format($payment->amount, 2) }}</p>
        <p><strong>Payment Method:</strong> {{ $payment->payment_method }}</p>
        <p><strong>Paid By:</strong> {{ optional($payment->paymentUser->administrator)->fname }} {{ optional($payment->paymentUser->administrator)->sname }}</p>
        <p><strong>Date Paid:</strong> {{ \Carbon\Carbon::parse($payment->paid_at)->format('j F, Y') }}</p>

        <hr>
        <p class="text-center">Thank you for choosing DARON!</p>

        <img src="{{ public_path('/media/paid.png') }}" class="stamp" alt="Paid Stamp">
    </div>
</body>
</html>
