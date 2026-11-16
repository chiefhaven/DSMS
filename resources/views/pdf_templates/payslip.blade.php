<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

    @include('pdf_templates.partials.header')

    <div class="container" style="padding: 40px 60px;">
        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    @if(!empty($setting?->logo) && file_exists(public_path("media/{$setting->logo}")))
                        <img src="{{ public_path("media/{$setting->logo}") }}" alt="{{ $setting->school_name ?? 'School logo' }}" style="max-width: 260px; height: auto;">
                    @else
                        <h2 style="margin: 0;">{{ $setting->school_name ?? 'Driving School' }}</h2>
                    @endif
                    <p style="margin: 6px 0 0; font-size: 11px;">
                        {{ $setting->address ?? '' }}<br>
                        {{ $setting->email ?? '' }} {{ !empty($setting->phone_1) ? '| ' . $setting->phone_1 : '' }}
                    </p>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right;">
                    <h2 style="margin: 0; text-transform: uppercase;">Payslip</h2>
                    <p style="margin: 6px 0 0; font-size: 12px;">
                        <strong>Pay month:</strong> {{ $monthLabel }}<br>
                        <strong>Status:</strong> {{ ucfirst($payroll->status) }}<br>
                        @if($payroll->payment_date)
                            <strong>Payment date:</strong> {{ $payroll->payment_date->format('j F, Y') }}<br>
                        @endif
                        <strong>Reference:</strong> {{ strtoupper(substr($payroll->id, 0, 8)) }}
                    </p>
                </td>
            </tr>
        </table>

        <table class="table" style="width: 100%; font-size: 12px; margin-bottom: 20px;">
            <tbody>
                <tr>
                    <td style="width: 50%;"><strong>Employee:</strong> {{ $payroll->employee_name }}</td>
                    <td style="width: 50%;"><strong>Role:</strong> {{ $payroll->employee_type }}</td>
                </tr>
                @php($employee = $payroll->instructor ?? $payroll->administrator)
                <tr>
                    <td><strong>Designation:</strong> {{ $employee?->designation?->name ?? '-' }}</td>
                    <td><strong>HR department:</strong> {{ $employee?->hrDepartment?->name ?? '-' }}</td>
                </tr>
                @if($payroll->group)
                <tr>
                    <td colspan="2"><strong>Payroll group:</strong> {{ $payroll->group->name }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <table class="table table-striped" style="width: 100%; font-size: 12px;">
            <thead style="color: #ffffff !important; background-color: #0665d0;">
                <th class="invoice-td" style="text-align: left;">Earnings &amp; deductions</th>
                <th class="invoice-td" style="text-align: right;">Amount (K)</th>
            </thead>
            <tbody>
                <tr>
                    <td class="invoice-td">Basic salary</td>
                    <td class="invoice-td" style="text-align: right;">{{ number_format((float) $payroll->basic_salary, 2) }}</td>
                </tr>
                <tr>
                    <td class="invoice-td">Allowances</td>
                    <td class="invoice-td" style="text-align: right;">{{ number_format((float) $payroll->allowances, 2) }}</td>
                </tr>
                <tr>
                    <td class="invoice-td">
                        Bonus
                        @if($payroll->instructor_id)
                            ({{ (int) $payroll->bonus_attendances }} attendances)
                        @endif
                    </td>
                    <td class="invoice-td" style="text-align: right;">{{ number_format((float) $payroll->bonus, 2) }}</td>
                </tr>
                <tr>
                    <td class="invoice-td"><strong>Gross earnings</strong></td>
                    <td class="invoice-td" style="text-align: right;"><strong>{{ number_format((float) $payroll->basic_salary + (float) $payroll->allowances + (float) $payroll->bonus, 2) }}</strong></td>
                </tr>
                <tr>
                    <td class="invoice-td">Deductions</td>
                    <td class="invoice-td" style="text-align: right;">({{ number_format((float) $payroll->deductions, 2) }})</td>
                </tr>
                <tr style="background-color: #eef4fc;">
                    <td class="invoice-td"><strong>Net pay</strong></td>
                    <td class="invoice-td" style="text-align: right;"><strong>K{{ number_format((float) $payroll->net_pay, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <p style="font-size: 10px; color: #777; margin-top: 30px;">
            Processed by {{ $payroll->processor->name ?? 'System' }} |
            Generated on {{ now()->format('j F, Y H:i') }} |
            This is a system-generated payslip.
        </p>
    </div>

</body>
</html>
