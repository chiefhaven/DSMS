<div class="content content-full">
    <div class="block-content position-relative" style="height: auto;">
        <div class="qr-camera-frame">
            <video class="form-control" id="webcam-preview" autoplay muted playsinline></video>
            <div class="scan-overlay" aria-hidden="true">
                <div class="scan-line"></div>
            </div>
            <div class="qr-camera-corner qr-camera-corner-top-left"></div>
            <div class="qr-camera-corner qr-camera-corner-top-right"></div>
            <div class="qr-camera-corner qr-camera-corner-bottom-left"></div>
            <div class="qr-camera-corner qr-camera-corner-bottom-right"></div>
            <div id="qr-scan-loading" class="qr-scan-loading" role="status" aria-live="polite" hidden>
                <span class="qr-scan-loading-spinner" aria-hidden="true"></span>
                <strong>QR code scanned</strong>
                <span>Opening payment details...</span>
                <div class="qr-scan-progress" role="progressbar" aria-label="Opening payment details">
                    <span></span>
                </div>
            </div>
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
        const cameraFrame = document.querySelector('.qr-camera-frame');
        const loadingIndicator = document.getElementById('qr-scan-loading');
        const resultMessage = document.getElementById('result');
        let isScanning = false;
        let scanHandled = false;
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

                    if (err && !scanHandled && !isExpectedError(err)) {
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
            const stream = videoElement.srcObject;
            if (stream && typeof stream.getTracks === 'function') {
                stream.getTracks().forEach(track => track.stop());
            }
            videoElement.pause();
            videoElement.srcObject = null;
            isScanning = false;
        }

        function isExpectedError(err) {
            return err instanceof ZXing.NotFoundException ||
                   err instanceof ZXing.ChecksumException ||
                   err instanceof ZXing.FormatException;
        }

        async function handleScanResult(scannedUrl) {
            if (scanHandled) return;
            scanHandled = true;
            stopScanning();

            if (!scannedUrl.includes(ALLOWED_DOMAIN)) {
                await Swal.fire({
                    title: 'Invalid Document',
                    text: 'This QR code is not from Daron\'s system',
                    icon: 'error'
                });
                scanHandled = false;
                startScanning();
                return;
            }

            const redirectPath = scannedUrl.replace(ALLOWED_DOMAIN, '');
            if (!isValidPath(redirectPath)) {
                await showError('Invalid document path');
                scanHandled = false;
                startScanning();
                return;
            }

            cameraFrame.classList.add('is-loading');
            loadingIndicator.hidden = false;
            resultMessage.textContent = 'Opening payment details...';
            window.setTimeout(() => {
                window.location.href = redirectPath;
            }, 200);
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

