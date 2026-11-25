<script>
    (function () {
        const modal = document.getElementById('editStudentTrnModal');
        const form = document.getElementById('editStudentTrnForm');
        const video = document.getElementById('trnCameraVideo');
        const canvas = document.getElementById('trnCameraCanvas');
        const preview = document.getElementById('trnPicturePreview');
        const placeholder = document.getElementById('trnCameraPlaceholder');
        const openCameraButton = document.getElementById('startTrnCamera');
        const captureButton = document.getElementById('captureTrnPicture');
        const cameraError = document.getElementById('trnCameraError');
        const pictureError = document.getElementById('trnPictureError');
        const trnInput = document.getElementById('editStudentTrnInput');
        const studentName = document.getElementById('editStudentTrnStudentName');
        const studentId = document.getElementById('editStudentTrnStudentId');
        const stepLabel = document.getElementById('trnStepLabel');
        const stepOne = document.getElementById('trnStepOne');
        const stepTwo = document.getElementById('trnStepTwo');
        const nextStepButton = document.getElementById('trnStepNext');
        const backStepButton = document.getElementById('trnStepBack');
        const saveButton = document.getElementById('trnStepSave');
        let cameraStream = null;
        let capturedPicture = null;
        let previewObjectUrl = null;
        let cameraActive = false;

        const hasPicture = () => Boolean(capturedPicture || preview.dataset.savedUrl);
        const updateSaveState = () => {
            saveButton.disabled = !hasPicture();
        };

        const updatePreview = (url, saved = false) => {
            if (saved) preview.dataset.savedUrl = url || '';
            preview.src = url || '';
            preview.classList.toggle('d-none', !url);
            placeholder.classList.toggle('d-none', Boolean(url) || cameraActive);
            updateSaveState();
        };

        const clearCapturedPicture = () => {
            capturedPicture = null;
            if (previewObjectUrl) {
                URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = null;
            }
        };

        const stopCamera = () => {
            cameraActive = false;
            if (cameraStream) {
                cameraStream.getTracks().forEach((track) => track.stop());
                cameraStream = null;
            }
            video.srcObject = null;
            video.classList.add('d-none');
            captureButton.disabled = true;
            captureButton.classList.add('d-none');
            updatePreview(capturedPicture ? previewObjectUrl : (preview.dataset.savedUrl || ''));
        };

        const clearPictureError = () => {
            pictureError.textContent = '';
        };

        const setStep = (step) => {
            const isPictureStep = step === 2;
            stepOne.classList.toggle('d-none', isPictureStep);
            stepTwo.classList.toggle('d-none', !isPictureStep);
            stepLabel.textContent = isPictureStep ? 'Step 2 of 2: Take a picture' : 'Step 1 of 2: Enter TRN';
            nextStepButton.classList.toggle('d-none', isPictureStep);
            backStepButton.classList.toggle('d-none', !isPictureStep);
            saveButton.classList.toggle('d-none', !isPictureStep);
            updateSaveState();
        };

        const startCamera = async () => {
            cameraError.textContent = '';
            clearPictureError();
            const previousPreview = capturedPicture ? previewObjectUrl : (preview.dataset.savedUrl || '');

            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                cameraError.textContent = 'Camera access requires a supported browser and a secure connection.';
                return;
            }

            stopCamera();
            updatePreview('');
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });
                cameraActive = true;
                video.srcObject = cameraStream;
                video.classList.remove('d-none');
                placeholder.classList.add('d-none');
                await video.play();
                captureButton.disabled = false;
                captureButton.classList.remove('d-none');
                openCameraButton.textContent = 'Retake photo';
            } catch (error) {
                stopCamera();
                updatePreview(previousPreview);
                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                    cameraError.textContent = 'Camera access was denied. Allow camera access and try again.';
                } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                    cameraError.textContent = 'No camera was found on this device.';
                } else {
                    cameraError.textContent = 'Unable to open the camera. Check camera permissions and try again.';
                }
            }
        };

        const goToPictureStep = () => {
            setStep(2);
            startCamera();
        };

        window.studentTrnCamera = {
            getCapturedPicture: () => capturedPicture,
            clearCapturedPicture,
            updatePreview,
            stopCamera,
            showStep: (step) => {
                if (step === 1) stopCamera();
                setStep(step);
            },
            goToPictureStep,
            isPictureStep: () => !stepTwo.classList.contains('d-none'),
            hasPicture,
            showPictureError: (message) => {
                pictureError.textContent = message;
            },
        };

        nextStepButton.addEventListener('click', () => {
            goToPictureStep();
        });

        backStepButton.addEventListener('click', () => {
            stopCamera();
            updatePreview(capturedPicture ? previewObjectUrl : (preview.dataset.savedUrl || ''));
            setStep(1);
            trnInput.focus();
        });
        openCameraButton.addEventListener('click', startCamera);

        captureButton.addEventListener('click', async () => {
            if (!video.videoWidth || !video.videoHeight) {
                cameraError.textContent = 'The camera is not ready yet. Please wait and try again.';
                return;
            }

            const scale = Math.min(1, 1600 / Math.max(video.videoWidth, video.videoHeight));
            canvas.width = Math.round(video.videoWidth * scale);
            canvas.height = Math.round(video.videoHeight * scale);
            const context = canvas.getContext('2d');
            if (!context) {
                cameraError.textContent = 'Unable to capture an image from the camera.';
                return;
            }

            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
            if (!blob) {
                cameraError.textContent = 'Unable to capture an image from the camera.';
                return;
            }

            clearCapturedPicture();
            capturedPicture = new File([blob], `trn-picture-${Date.now()}.jpg`, { type: 'image/jpeg' });
            previewObjectUrl = URL.createObjectURL(blob);
            updatePreview(previewObjectUrl);
            cameraError.textContent = '';
            stopCamera();
        });

        modal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            stopCamera();
            clearCapturedPicture();
            setStep(1);
            updatePreview(preview.dataset.savedUrl || '', true);
            cameraError.textContent = '';
            clearPictureError();
            openCameraButton.textContent = 'Open camera';

            if (trigger && trigger.dataset.id) {
                form.action = form.dataset.urlTemplate.replace('__STUDENT__', encodeURIComponent(trigger.dataset.id));
                form.dataset.studentId = trigger.dataset.id;
                studentId.value = trigger.dataset.id;
                studentName.textContent = `${trigger.dataset.fname || ''} ${trigger.dataset.mname || ''} ${trigger.dataset.sname || ''}`.trim();
                trnInput.value = trigger.dataset.trn || '';
                trnInput.classList.remove('is-invalid');
                document.getElementById('editStudentTrnError').textContent = '';
                document.getElementById('editStudentTrnError').classList.remove('d-block');
                updatePreview(trigger.dataset.trnPictureUrl || '', true);
            }
        });

        modal.addEventListener('hidden.bs.modal', () => {
            clearCapturedPicture();
            stopCamera();
            updatePreview(preview.dataset.savedUrl || '', true);
        });
    })();
</script>
