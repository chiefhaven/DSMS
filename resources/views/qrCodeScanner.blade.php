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
    document.addEventListener('DOMContentLoaded', async () => {
        // Verify ZXing is loaded
        if (typeof ZXing === 'undefined') {
            await Swal.fire('Error', 'Scanner library failed to load', 'error');
            return;
        }

        const DOMAIN = 'https://www.dsms.darondrivingschool.com';
        let isScanning = false;
        const codeReader = new ZXing.BrowserQRCodeReader();
        const videoElement = document.getElementById('webcam-preview');

        // Check camera permissions first
        try {
            await navigator.mediaDevices.getUserMedia({ video: true });
        } catch (error) {
            await Swal.fire('Camera Access Required', 'Please enable camera permissions', 'warning');
            return;
        }

        async function startScanning() {
            if (isScanning) return;
            isScanning = true;

            try {
                await codeReader.decodeFromVideoDevice(null, videoElement, async (result, err) => {
                    if (result) {
                        try {
                            await handleScanResult(result.text);
                        } catch (error) {
                            console.error('Result handling error:', error);
                        }
                    }

                    if (err && !isExpectedError(err)) {
                        console.error('Scanning error:', err);
                        stopScanning();
                    }
                });
            } catch (error) {
                console.error('Scanner initialization error:', error);
                await Swal.fire('Scanner Error', 'Failed to initialize scanner', 'error');
                stopScanning();
            }
        }

        function stopScanning() {
            codeReader.reset();
            isScanning = false;
        }

        function isExpectedError(err) {
            return err instanceof ZXing.NotFoundException ||
                   err instanceof ZXing.ChecksumException ||
                   err instanceof ZXing.FormatException;
        }

        async function handleScanResult(url) {
            stopScanning();

            if (!url) {
                await Swal.fire('Invalid QR Code', 'No data found', 'warning');
                return;
            }

            await Swal.fire({
                title: 'Scan Complete!',
                text: 'Checking student and redirecting...',
                icon: 'success'
            });

            if (url.includes(DOMAIN)) {
                const redirectPath = url.replace(DOMAIN, '');

                // Security check for valid path
                if (isValidPath(redirectPath)) {
                    window.location.href = redirectPath;
                } else {
                    await Swal.fire('Security Alert', 'Invalid redirect path detected', 'error');
                }
            } else {
                await Swal.fire(
                    'Invalid Document',
                    'QR code doesn\'t belong to Daron\'s documents',
                    'error'
                );
            }
        }

        function isValidPath(path) {
            // Add your path validation logic here
            return path.startsWith('/') && !path.includes('..');
        }

        // Start scanning automatically or add a start button
        startScanning();
    });
</script>

@endsection
