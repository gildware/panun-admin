(function () {
    'use strict';

    const lastValidFiles = new Map();

    function parseAccept(accept) {
        if (!accept) return [];
        return accept.split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
    }

    function fileMatchesAccept(file, accepted) {
        if (!accepted || !accepted.length) return true;
        const fileType = (file.type || '').toLowerCase();
        const name = file.name || '';
        const ext = '.' + name.split('.').pop().toLowerCase();
        for (const a of accepted) {
            if (a.startsWith('.') && ext === a) return true;
            if (a.includes('/') && a.endsWith('/*') && fileType.startsWith(a.split('/')[0] + '/')) return true;
            if (a === fileType) return true;
            if (a === ext) return true;
        }
        return false;
    }

    function getMaxBytes(input) {
        const m = $(input).attr('data-maxFileSize');
        const mb = (m && !isNaN(parseFloat(m))) ? parseFloat(m) : 20;
        return mb * 1024 * 1024;
    }

    function showError(msg) {
        if (typeof toastr !== 'undefined' && toastr.error) toastr.error(msg);
        else { console.error(msg); alert(msg); }
    }

    function restorePreview(input, file) {
        const previewImg = document.querySelector(`[data-preview-for="${input.id}"]`);
        if (!previewImg) return;
        if (file) previewImg.src = URL.createObjectURL(file);
        else {
            const placeholder = input.getAttribute('data-placeholder');
            previewImg.src = placeholder || '';
        }
    }

    function validatingChangeHandler(ev) {
        if (!ev || !ev.target || ev.target.tagName !== 'INPUT' || ev.target.type !== 'file') return;

        const input = ev.target;
        const files = Array.from(input.files || []);
        if (!files.length) return;

        const accepted = parseAccept(input.getAttribute('accept') || '');
        const maxBytes = getMaxBytes(input);

        const isMultiple = !!input.multiple;
        const validFiles = [];
        let anyInvalid = false;

        for (const file of files) {
            const name = file.name || 'file';
            if (!fileMatchesAccept(file, accepted)) {
                showError(`"${name}" is not an allowed file type.`);
                anyInvalid = true;
                break;
            }

            if (file.size > maxBytes) {
                const mb = Math.round((maxBytes / (1024 * 1024)) * 100) / 100;
                showError(`"${name}" exceeds ${mb}MB limit.`);
                anyInvalid = true;
                break;
            }
            validFiles.push(file);
        }

        if (anyInvalid) {
            const lastStored = lastValidFiles.get(input);
            if (lastStored) {
                const dt = new DataTransfer();
                const toRestore = Array.isArray(lastStored) ? lastStored : [lastStored];
                toRestore.forEach(function (f) {
                    if (f) dt.items.add(f);
                });
                input.files = dt.files;
            } else {
                input.value = '';
            }
            const previewRef = Array.isArray(lastStored) ? lastStored[0] : lastStored;
            restorePreview(input, previewRef || null);
            ev.stopImmediatePropagation();
            ev.preventDefault();
            return false;
        }

        if (validFiles.length) {
            const dt = new DataTransfer();
            if (isMultiple) {
                validFiles.forEach(function (f) {
                    if (f) dt.items.add(f);
                });
                lastValidFiles.set(input, validFiles.slice());
            } else {
                const one = validFiles[validFiles.length - 1];
                dt.items.add(one);
                lastValidFiles.set(input, one);
            }
            input.files = dt.files;
            restorePreview(input, validFiles[0] || null);
        }

        return true;
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.type === 'file') {
            validatingChangeHandler(e);
        }
    }, true);

})();


