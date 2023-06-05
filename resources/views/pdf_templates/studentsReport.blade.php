<!DOCTYPE html>
<html>
<head>
    @include('pdf_templates.css')
</head>
<body>

@include('pdf_templates.partials.header')

<div class="container invoice">
    <div class="row">
        <div class="col-12">
            <h3 style="text-align:center">STUDENTS REPORT</h3>
        </div>
    </div>
    <table class="table table-responsive table-striped" style="font-size:12px; background-color: #ffffff; margin: 20px 0;">
        <thead style="color: #ffffff !important; background-color:#0665d0;">
            <th class="invoice-td text">#</th>
            <th class="invoice-td text">Student</th>
            <th class="invoice-td amount">Invoice Total</th>
            <th class="invoice-td amount">Balance</th>
            <th class="invoice-td amount">Progress</th>
        </thead>
        <tbody>
            @foreach ($student as $index => $student)
                @if( $index % 34 == 0 )
                    @if ($index != 0)
                        <div class="page-break"></div>
                    @endif
                @endif
                <div class="page-break"></div>
                <tr class="py-1" style="padding-top: 0px; padding-bottom: 0px;">
                    <td class="invoice-td text">
                        {{$index}}
                    </td>
                    <td class="invoice-td text">
                        <span class = "capitalize">{{$student->sname}} {{$student->mname}} <b>{{$student->fname}}</b></span>
                    </td>
                    <td class="invoice-td amount">
                        @if (isset($student->invoice->course_price))
                            K{{number_format($student->invoice->course_price, 2)}}
                        @else
                            -
                        @endif
                    </td>
                    <td class="invoice-td amount">
                        @if (isset($student->invoice->invoice_balance))
                            K{{number_format($student->invoice->invoice_balance, 2)}}
                        @else
                            -
                        @endif
                    </td>
                    <td class="invoice-td amount">
                        {{number_format($student->attendance->count()/$student->course->duration*100)}}%
                        <br><div style="font-size: 8px">{{$student->attendance->count()}} of {{ $student->course->duration }} days done!</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
