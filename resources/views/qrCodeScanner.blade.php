@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">ScanQR Code</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">App</li>
            <li class="breadcrumb-item active" aria-current="page">dashboard</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block-content">
        <video class="form-control" id="webcam-preview"></video>
        <p id="result"></p>
    </div>
  </div>
  <!-- END Hero -->

  <script>
    var url = '';
    const codeReader = new ZXing.BrowserQRCodeReader();

    codeReader.decodeFromVideoDevice(null, 'webcam-preview', (result, err) => {
        if (result) {
            codeReader.scannerEnabled = false
            url = result.text
            Swal.fire(
                'Scan complete!',
                'Checking student and redirecting...'
        )

        if(url.includes('https://www.dsms.darondrivingschool.com')){
            var ret = url.replace('https://www.dsms.darondrivingschool.com','')
            window.location.replace(ret)
        }

        else{
            Swal.fire(
                'Invalid Document',
                'QR code doesn\'t belong to Daron\'s documents...'
            )
        }


        }

        if (err) {
        // As long as this error belongs into one of the following categories
        // the code reader is going to continue as excepted. Any other error
        // will stop the decoding loop.
        //
        // Excepted Exceptions:
        //
        //  - NotFoundException
        //  - ChecksumException
        //  - FormatException

        if (err instanceof ZXing.NotFoundException) {
            console.log('No QR code found.')
        }

        if (err instanceof ZXing.ChecksumException) {
            console.log('A code was found, but it\'s read value was not valid.')
        }

        if (err instanceof ZXing.FormatException) {
            console.log('A code was found, but it was in a invalid format.')
        }
        }
    })


    </script>

@endsection
