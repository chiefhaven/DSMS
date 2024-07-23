<style>
        @page {
            margin: 0;
        }

        .page-break {
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: unset;
        }

        p{
            line-height: 1.4 !important;
        }

        body {
            color: #000000;
            background: #FFFFFF;
            font-family : DejaVu Sans, Helvetica, sans-serif;
            font-size: 12px;
            margin-bottom: 50px;
            margin:0;
            padding:0;
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
            color: ;
            white-space: nowrap;
            font-weight: normal;
        }

        tbody {
            padding: 5px 10px;
            color: #000000;
            white-space: nowrap;
            font-weight: normal;
            text-align: left;
        }

        td {
            padding: 0px;
            text-align: left;
        }

        .invoice-td {
            padding: 10px 10px;
        }

        table.alternate tr:nth-child(odd) td {
            background: #000000;
        }

        th.amount, td.amount {
            text-align: right;
        }

        th.text, td.text {
            text-align: left;
        }

        .info {
            color: #000000;
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
          border: 1px solid #000000;
        }

        .invoice{
                position: absolute;
                z-index: 999;
                margin: 1em 6em !important;
        }

        .reference_letters{
                position: absolute;
                z-index: 999;
                margin: 4em 7em !important;
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
            top: -100px;
            left: -550px;
            height: 200%;
            width: 200%;
            font-size: 20px;
            pointer-events: none;
            -webkit-transform: rotate(23deg);
            line-height: 0.5;
            color: #999999;
        }

        .watermark{
            height: 34.5cm;
            width: 24cm;
            overflow: hidden;
            position: absolute;
            top: -50px;
            left: -50px;
            margin:0;
            padding:0;
        }

        .capitalize {
            text-transform: lowercase;
            display: inline-block;
        }

        .capitalize::first-letter {
            text-transform: uppercase
        }

    </style>
