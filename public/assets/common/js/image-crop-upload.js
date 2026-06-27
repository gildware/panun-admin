/**
 * Centralized image crop for admin/provider panels.
 * Intercepts single-image file inputs, opens a Cropper.js modal with the
 * aspect ratio inferred from DOM classes, data attributes, or field names,
 * then replaces the input file before existing preview handlers run.
 */
(function (window, document) {
    'use strict';

    if (window.ImageCropUpload && window.ImageCropUpload.initialized) {
        return;
    }

    const RATIO_CLASS_MAP = {
        'ratio-1-1': [1, 1],
        'ratio-1': [1, 1],
        'ratio-2-1': [2, 1],
        'ratio-3-1': [3, 1],
        'ratio-3-2': [3, 2],
        'ratio-4-1': [4, 1],
        'ratio-4-2': [4, 2],
        'ratio-5-1': [5, 1],
        'ratio-7-1': [7, 1],
    };

    const FIELD_RATIO_MAP = {
        profile_image: [1, 1],
        thumbnail: [1, 1],
        image: [1, 1],
        logo: [1, 1],
        business_favicon: [1, 1],
        contact_person_photo: [1, 1],
        meta_image: [1, 1],
        banner_image: [2, 1],
        business_logo: [3, 1],
        gateway_image: [3, 1],
    };

    const SKIP_INPUT_SELECTOR = [
        'input[data-crop="false"]',
        'input[data-skip-crop="true"]',
        'input[multiple]',
        '.document_input',
        '#msgfilesValue',
        '.image-compressor',
        '#aiImageUpload',
        'input[accept*=".pdf"]',
        'input[accept*=".csv"]',
        'input[accept*=".json"]',
        'input[accept*=".zip"]',
        'input[accept*=".xlsx"]',
        'input[accept*=".xls"]',
        'input[accept*=".doc"]',
        'input[accept*="video"]',
        'input[accept*="audio"]',
    ].join(', ');

    let modalEl = null;
    let cropper = null;
    let activeInput = null;
    let activeFile = null;
    let activeRatio = null;
    let activeObjectUrl = null;
    let resolveCropPromise = null;

    function revokeActiveObjectUrl() {
        if (activeObjectUrl) {
            URL.revokeObjectURL(activeObjectUrl);
            activeObjectUrl = null;
        }
    }

    function ensureModal() {
        if (modalEl) {
            return modalEl;
        }

        modalEl = document.createElement('div');
        modalEl.className = 'modal fade image-crop-modal';
        modalEl.id = 'imageCropUploadModal';
        modalEl.tabIndex = -1;
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title image-crop-modal__title">Crop image</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="image-crop-modal__canvas-wrap">
                            <img class="image-crop-modal__image" alt="Crop preview">
                        </div>
                        <p class="image-crop-modal__ratio-hint px-3 py-2 mb-0 text-muted fs-12"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary image-crop-modal__apply">Apply crop</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalEl);

        const applyBtn = modalEl.querySelector('.image-crop-modal__apply');
        applyBtn.addEventListener('click', onApplyCrop);

        modalEl.addEventListener('hidden.bs.modal', onModalHidden);

        return modalEl;
    }

    function initCropperOnImage(img) {
        if (!img || !activeFile) {
            return;
        }

        destroyCropper();

        const aspectRatio = activeRatio ? activeRatio[0] / activeRatio[1] : NaN;
        cropper = new Cropper(img, {
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.92,
            responsive: true,
            restore: false,
            guides: true,
            center: true,
            highlight: true,
            background: true,
            movable: true,
            zoomable: true,
            zoomOnWheel: true,
            scalable: false,
            rotatable: false,
            checkOrientation: true,
            aspectRatio: isNaN(aspectRatio) ? NaN : aspectRatio,
            ready: function () {
                if (cropper) {
                    cropper.resize();
                }
            },
        });
    }

    function isImageFile(file) {
        return !!(file && file.type && file.type.startsWith('image/'));
    }

    function isImageInput(input) {
        if (!input || input.type !== 'file') {
            return false;
        }
        if (input.matches(SKIP_INPUT_SELECTOR)) {
            return false;
        }

        const accept = (input.getAttribute('accept') || '').toLowerCase();
        if (!accept) {
            return true;
        }
        if (
            accept.includes('image') ||
            accept.includes('.jpg') ||
            accept.includes('.jpeg') ||
            accept.includes('.png') ||
            accept.includes('.webp') ||
            accept.includes('.gif')
        ) {
            return true;
        }

        return false;
    }

    function shouldSkipCrop(input) {
        if (!input) {
            return true;
        }
        if (input.dataset.cropProcessed === 'true') {
            return true;
        }
        if (input.dataset.crop === 'false' || input.dataset.skipCrop === 'true') {
            return true;
        }
        if (input.matches(SKIP_INPUT_SELECTOR)) {
            return true;
        }
        if (input.multiple) {
            return true;
        }
        return false;
    }

    function parseRatioString(value) {
        if (!value) {
            return null;
        }
        const normalized = String(value).trim().replace(/\s+/g, '');
        const match = normalized.match(/^(\d+(?:\.\d+)?)[/:x](\d+(?:\.\d+)?)$/i);
        if (!match) {
            return null;
        }
        return [parseFloat(match[1]), parseFloat(match[2])];
    }

    function ratioFromClasses(element) {
        if (!element) {
            return null;
        }
        for (const className of Object.keys(RATIO_CLASS_MAP)) {
            if (element.classList.contains(className)) {
                return RATIO_CLASS_MAP[className];
            }
        }
        return null;
    }

    function ratioFromFieldName(input) {
        const name = (input.name || '').replace(/\[\]$/, '').trim();
        if (!name) {
            return null;
        }
        if (FIELD_RATIO_MAP[name]) {
            return FIELD_RATIO_MAP[name];
        }
        if (name.includes('cover') || name.includes('banner')) {
            const uploadFile = input.closest('.upload-file, .upload-file-new, .global-image-upload');
            if (uploadFile && (uploadFile.querySelector('.upload-file__img_banner') || uploadFile.classList.contains('ratio-3-1'))) {
                return [3, 1];
            }
            return [2, 1];
        }
        if (name.includes('logo') && name.includes('business')) {
            return [3, 1];
        }
        if (name.includes('logo') || name.includes('thumbnail') || name.includes('profile') || name.includes('icon')) {
            return [1, 1];
        }
        if (name.includes('identity')) {
            return [2, 1];
        }
        return null;
    }

    function ratioFromUploadWrapper(input) {
        const container = input.closest('.upload-file-new, .upload-file, .global-image-upload');
        if (!container) {
            return null;
        }

        const wrapper = container.querySelector(
            '.upload-file-new__wrapper, .upload-file__wrapper, .global-image-upload__wrapper'
        );
        return ratioFromClasses(wrapper);
    }

    function resolveCropRatio(input) {
        const explicit = parseRatioString(input.dataset.cropRatio);
        if (explicit) {
            return explicit;
        }

        const fromWrapper = ratioFromUploadWrapper(input);
        if (fromWrapper) {
            return fromWrapper;
        }

        let node = input;
        while (node && node !== document.body) {
            const fromClass = ratioFromClasses(node);
            if (fromClass) {
                return fromClass;
            }
            node = node.parentElement;
        }

        if (input.closest('.upload-file__img_banner')) {
            return [3, 1];
        }
        const uploadFile = input.closest('.upload-file');
        if (uploadFile && uploadFile.querySelector('.upload-file__img_banner')) {
            return [3, 1];
        }
        if (input.closest('.global-image-upload.ratio-7-1')) {
            return [7, 1];
        }
        if (input.closest('.global-image-upload.ratio-3-1')) {
            return [3, 1];
        }
        if (input.closest('.global-image-upload.ratio-2-1')) {
            return [2, 1];
        }

        const fromField = ratioFromFieldName(input);
        if (fromField) {
            return fromField;
        }

        return [1, 1];
    }

    function ratioLabel(ratio) {
        if (!ratio) {
            return 'Free crop';
        }
        return ratio[0] + ':' + ratio[1];
    }

    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function onModalHidden() {
        destroyCropper();
        revokeActiveObjectUrl();
        const img = modalEl.querySelector('.image-crop-modal__image');
        if (img) {
            img.onload = null;
            img.removeAttribute('src');
            img.removeAttribute('style');
        }
        if (resolveCropPromise) {
            resolveCropPromise(null);
            resolveCropPromise = null;
        }
        activeInput = null;
        activeFile = null;
        activeRatio = null;
    }

    function onApplyCrop() {
        if (!cropper || !activeInput || !activeFile) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Crop tool is not ready yet. Close and try again.');
            }
            return;
        }

        const canvas = cropper.getCroppedCanvas({
            maxWidth: 4096,
            maxHeight: 4096,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            return;
        }

        const outputType = activeFile.type === 'image/png' ? 'image/png' : 'image/jpeg';
        const quality = outputType === 'image/jpeg' ? 0.92 : undefined;

        canvas.toBlob(
            function (blob) {
                if (!blob) {
                    if (resolveCropPromise) {
                        resolveCropPromise(null);
                        resolveCropPromise = null;
                    }
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                    return;
                }

                const baseName = (activeFile.name || 'image').replace(/\.[^.]+$/, '');
                const extension = outputType === 'image/png' ? '.png' : '.jpg';
                const croppedFile = new File([blob], baseName + extension, {
                    type: outputType,
                    lastModified: Date.now(),
                });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);
                activeInput.files = dataTransfer.files;

                bootstrap.Modal.getInstance(modalEl)?.hide();

                if (resolveCropPromise) {
                    resolveCropPromise(croppedFile);
                    resolveCropPromise = null;
                }

                activeInput.dataset.cropProcessed = 'true';
                activeInput.dispatchEvent(new Event('change', { bubbles: true }));
                delete activeInput.dataset.cropProcessed;
            },
            outputType,
            quality
        );
    }

    function assignFileToInput(input, file) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
    }

    function openCropModal(input, file) {
        return new Promise(function (resolve) {
            if (typeof Cropper === 'undefined' || typeof bootstrap === 'undefined') {
                assignFileToInput(input, file);
                input.dataset.cropProcessed = 'true';
                input.dispatchEvent(new Event('change', { bubbles: true }));
                delete input.dataset.cropProcessed;
                resolve(file);
                return;
            }

            ensureModal();
            activeInput = input;
            activeFile = file;
            activeRatio = resolveCropRatio(input);
            resolveCropPromise = resolve;

            const img = modalEl.querySelector('.image-crop-modal__image');
            const hint = modalEl.querySelector('.image-crop-modal__ratio-hint');
            const title = modalEl.querySelector('.image-crop-modal__title');

            destroyCropper();
            revokeActiveObjectUrl();
            if (img) {
                img.onload = null;
                img.removeAttribute('src');
                img.removeAttribute('style');
            }

            if (title) {
                title.textContent = 'Crop image';
            }
            if (hint) {
                hint.textContent =
                    'Drag and zoom to choose the visible area. Display ratio: ' + ratioLabel(activeRatio) + '.';
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            let modalIsVisible = false;

            function tryInitCropper() {
                if (!modalIsVisible || cropper || !img) {
                    return;
                }
                if (!img.complete || img.naturalWidth <= 0 || img.naturalHeight <= 0) {
                    return;
                }
                initCropperOnImage(img);
            }

            function handleShown() {
                modalEl.removeEventListener('shown.bs.modal', handleShown);
                modalIsVisible = true;
                tryInitCropper();
            }

            function handleImageReady() {
                img.onload = null;
                tryInitCropper();
            }

            modalEl.addEventListener('shown.bs.modal', handleShown);
            modal.show();

            if (img) {
                img.onload = handleImageReady;
                activeObjectUrl = URL.createObjectURL(file);
                img.src = activeObjectUrl;
                if (img.complete && img.naturalWidth > 0) {
                    handleImageReady();
                }
            }
        });
    }

    function handleInputChange(event) {
        const input = event.target;
        if (shouldSkipCrop(input) || !isImageInput(input)) {
            return;
        }

        const files = input.files;
        if (!files || !files.length) {
            return;
        }

        if (files.length > 1) {
            return;
        }

        const file = files[0];
        if (!isImageFile(file)) {
            return;
        }

        event.stopImmediatePropagation();
        event.preventDefault();

        const originalInput = input;
        originalInput.value = '';

        openCropModal(originalInput, file).then(function (result) {
            if (!result) {
                originalInput.value = '';
            }
        });
    }

    function bind() {
        document.addEventListener('change', handleInputChange, true);
    }

    window.ImageCropUpload = {
        initialized: true,
        bind: bind,
        resolveCropRatio: resolveCropRatio,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})(window, document);
