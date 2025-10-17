<div class="content content-full">
    <div class="block-content position-relative" style="height: auto;">
        <video class="form-control" id="webcam-preview" autoplay></video>
        <div class="scan-overlay">
            <div class="scan-line"></div>
        </div>
        <p id="result" class="mt-2 text-center text-muted">Align QR code within the box</p>
    </div>
</div>

<script>
    // Wait for DOM and libraries to load
    document.addEventListener('DOMContentLoaded', async () => {
        // Configuration
        const ALLOWED_DOMAIN = 'https://www.dsms.darondrivingschool.com';
        const videoElement = document.getElementById('webcam-preview');
        let isScanning = false;
        let codeReader;

        // Verify ZXing loaded
        if (!window.ZXing) {
            await Swal.fire({
                icon: 'error',
                title: 'Scanner Error',
                text: 'QR scanner to load. Please refresh the page.'
            });
            return;
        }

        // Initialize scanner
        try {
            codeReader = new ZXing.BrowserQRCodeReader();

            // Start scanning automatically
            startScanning();
        } catch (error) {
            console.error('Scanner init error:', error);
            await showError('Failed to initialize scanner');
        }

        async function startScanning() {
            if (isScanning) return;
                isScanning = true;

            try {
                await codeReader.decodeFromVideoDevice(null, videoElement, async (result, err) => {
                    if (result) {
                        await handleScanResult(result.text);
                    }

                    if (err && !isExpectedError(err)) {
                        console.error('Scanning error:', err);
                        await showError('Scanner encountered an error');
                        stopScanning();
                    }
                });
            } catch (error) {
                console.error('Scanner error:', error);
                await showError('Failed to start camera');
                stopScanning();
            }
        }

        function stopScanning() {
            if (codeReader) {
                codeReader.reset();
            }
            isScanning = false;
        }

        function isExpectedError(err) {
            return err instanceof ZXing.NotFoundException ||
                   err instanceof ZXing.ChecksumException ||
                   err instanceof ZXing.FormatException;
        }

        async function handleScanResult(scannedUrl) {
            stopScanning();

            try {
                await Swal.fire({
                    title: 'Scan Complete!',
                    text: 'Verifying document...',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: true
                });

                if (scannedUrl.includes(ALLOWED_DOMAIN)) {
                    const redirectPath = scannedUrl.replace(ALLOWED_DOMAIN, '');

                    // Security check for valid path
                    if (isValidPath(redirectPath)) {
                        window.location.href = redirectPath;
                    } else {
                        await showError('Invalid document path');
                    }
                } else {
                    await Swal.fire({
                        title: 'Invalid Document',
                        text: 'This QR code is not from Daron\'s system',
                        icon: 'error'
                    });
                    startScanning(); // Resume scanning
                }
            } catch (error) {
                startScanning(); // Resume scanning after error
            }
        }

        function isValidPath(path) {
            // Basic security check - modify as needed
            return path && path.startsWith('/') &&
                   !path.includes('..') &&
                   !path.includes('//');
        }

        async function showError(message) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonText: 'OK'
            });
        }

        // Restart scanning if needed
        document.getElementById('rescan-btn')?.addEventListener('click', startScanning);
    });
    </script>

