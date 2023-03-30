<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 25px;
        }

        body {
            color: #001028;
            background: #FFFFFF;
            font-family : DejaVu Sans, Helvetica, sans-serif;
            font-size: 14px;
            margin-bottom: 50px;
        }

        a {
            color: #5D6975;
            text-decoration: none;
        }

        h1 {
            color: #5D6975;
            font-size: 2.8em;
            line-height: 1.4em;
            font-weight: bold;
            margin: 0;
        }

        table {
            width: 100%;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        th, .section-header {
            padding: 5px 10px;
            color: #5D6975;
            white-space: nowrap;
            font-weight: normal;
            text-align: center;
        }

        tbody {
            padding: 5px 10px;
            color: #5D6975;
            white-space: nowrap;
            font-weight: normal;
            text-align: center;
        }

        td {
            padding: 10px;
        }

        table.alternate tr:nth-child(odd) td {
            background: #F5F5F5;
        }

        th.amount, td.amount {
            text-align: right;
        }

        .info {
            color: #5D6975;
            font-weight: bold;
        }

        .terms {
            padding: 10px;
        }

        .footer {
            position: fixed;
            height: 50px;
            width: 100%;
            bottom: 0;
            text-align: center;
        }

       .table-striped tbody tr:nth-of-type(odd) {

            background-color: rgba(0, 0, 0, 0.05);

        }
    
    

        .table-bordered td {
          border: 1px solid #dee2e6;
        }
            
        .invoice{   
                position: absolute;
                z-index: 999;
                margin: 4em !important;
        }

        .items table{
            background: #ffffff;
        }


        #watermark {
            height: 100%;
            width: 100%;
            max-width: 100%;
            position: absolute;
            overflow: hidden;
            opacity: 0.1;
            z-index: 0;
        }

        #watermark img {
          max-width: 100%;
        }

        #watermark p {
            position: relative;
            top: -300px;
            left: -300px;
            height: 200%;
            width: 200%;
            font-size: 30px;
            pointer-events: none;
            -webkit-transform: rotate(23deg);
            line-height: 1;
            color: #999999;
        }

    </style>
</head>
<body>

<div class"" style="height: 30.5cm; width: 22cm; overflow: hidden; position: absolute; top: 0; left: 0;">
    <div id="watermark">
        <p>
            @for($i = 0; $i < 1000; $i++)

                {{$student->fname}} {{$student->sname}}

            @endfor
        </p>
    </div>
</div>

<div class="container invoice">
    <div class="row">
    <div class="col-8">
    <h1>DARON DRIVING SCHOOL</h1>
    <div class="row">
        <div class="col-12">
        <table class="table">
            <thead>
                <th style="width:70%"></th>
                <th></th>
            </thead>
            <tbody>
                <tr>
                    <td style="border: solid #ffffff00; text-align:left;">
                        The Regional Director<br>
                        Road Traffic and Safety Services<br>
                        P.O Box 101<br>
                        Lilongwe
                    </td>
                    <td style="border: solid #ffffff00;" valign="top">Date: 22/07/2022</td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>

    <p>Dear Sir/Madam,</p>

    <h3 style="text-transform: uppercase;">REFERENCE LETTER FOR {{$student->fname}} {{$student->sname}}</h3>

    <p>This letter serves as a reference for {{$student->fname}} {{$student->sname}} to support his application for a Traffic Registers Card. {{$student->fname}} {{$student->sname}} is one of our students here at DARON DRIVING SCHOOL and would like to apply for a traffic register card.</p>
     
    <p>Your assistance rendered to 
        @if($student->gender='Male')
            him
        @elseif($student->gender='Female')
            her
        @else
            him/her
        @endif
     will be highly appreciated.</p>

    <p>Yours Faithfully</p>    
    <p><img src="{{ public_path("media/signatures/authorizing-signature.png") }}" alt="" style="width: auto; height: 50px;"></p>
    <p><b>Chimwemwe Mboma</b><br>
</div>
</div>
</div>

</body>
</html>