<style>
        @page {
            margin: 30px 0 0 0;
        }

        .page-break {
            page-break-after: always;
            margin: 50px 0 0 0;
        }
        .page:last-child {
            page-break-after: never;
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
            overflow: visible;
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
            overflow: visible;
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
            overflow: visible;
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
        b,
        strong {
          font-weight: bolder;
        }

        small, .small {
          font-size: 0.875em;
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
                //position: absolute; //commented for it table dont overflow to second pages...
                z-index: 999;
                margin: 0 7em !important;
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
            position: fixed;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-list{
            page-break-after:always;
        }

        thead {
            display: table-header-group; /* Ensure header is repeated on each page */
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid; /* Prevent row breaks */
        }
        .text-lowercase {
            text-transform: lowercase !important;
        }

        .text-uppercase {
            text-transform: uppercase !important;
        }

        .text-capitalize {
            text-transform: capitalize !important;
        }

        blue-line-left{
            height: 300%;
            width: 5px;
            position:fixed;
            top:-90px;
            left: 30px;
            z-index: 999;
            background: blue;
        }

        black-line{
            height: 300%;
            width: 2px;
            position:fixed;
            top:-90px;
            left: 35px;
            z-index: 999;
            background: black;
        }

        blue-line-right{
            height: 300%;
            width: 3px;
            position:fixed;
            top:-90px;
            left: 37px;
            z-index: 999;
            background: blue;
        }

        .text-start {
            text-align: left !important;
        }

        .text-end {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        havenCrredit {
            display: inline-block; /* or use display: block; if you need a block element */
            transform: rotate(90deg);
            transform-origin: left bottom;
            left: 5px;
            position: absolute;
            font-size: 8px;
        }
    </style>
