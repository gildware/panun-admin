<style>
    .lead-view-modal-dialog {
        width: calc(100vw - 1.5rem);
        max-width: 1440px;
        height: calc(100vh - 1.5rem);
        margin: 0.75rem auto;
    }
    .lead-view-modal-dialog .modal-content {
        height: 100%;
        overflow: hidden;
        border: 0;
        border-radius: 0.75rem;
    }
    .lead-view-modal-dialog .modal-header { flex-shrink: 0; }
    .lead-view-modal-dialog .modal-body {
        position: relative;
        flex: 1 1 auto;
        min-height: 0;
        background: #f4f5f7;
    }
    .lead-view-modal-dialog iframe {
        display: block;
        width: 100%;
        height: 100%;
        border: 0;
        background: #fff;
    }
    .lead-view-modal__loading {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.86);
        z-index: 2;
    }
    tr.js-record-preview-row { cursor: pointer; }
    tr.js-record-preview-row:hover { background-color: rgba(13, 110, 253, 0.06); }
</style>

<div class="modal fade" id="leadViewModal" tabindex="-1" aria-labelledby="leadViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered lead-view-modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="leadViewModalLabel">{{ $previewTitle ?? translate('Details') }}</h5>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <a href="#"
                       id="lead-view-new-tab"
                       class="btn btn-sm btn--primary d-inline-flex align-items-center gap-1"
                       target="_blank"
                       rel="noopener noreferrer"
                       data-turbo="false">
                        <span class="material-icons" style="font-size: 18px;">open_in_new</span>
                        {{ translate('View_in_new_tab') }}
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="lead-view-modal__loading d-none" id="lead-view-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ translate('Loading') }}</span>
                    </div>
                </div>
                <iframe id="lead-view-frame" title="{{ $previewTitle ?? translate('Details') }}" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        function previewUrls(url) {
            const modalDest = new URL(url, window.location.origin);
            modalDest.searchParams.set('in_modal', '1');
            const tabDest = new URL(url, window.location.origin);
            tabDest.searchParams.delete('in_modal');
            tabDest.searchParams.delete('inModal');
            return { modal: modalDest.href, tab: tabDest.href };
        }

        window.openRecordPreview = function (url) {
            if (!url) {
                return;
            }
            const urls = previewUrls(url);
            const frame = document.getElementById('lead-view-frame');
            const modalEl = document.getElementById('leadViewModal');
            const loading = document.getElementById('lead-view-loading');
            const newTab = document.getElementById('lead-view-new-tab');
            if (!frame || !modalEl || typeof bootstrap === 'undefined') {
                window.location.href = urls.tab;
                return;
            }
            if (newTab) {
                newTab.href = urls.tab;
            }
            if (loading) {
                loading.classList.remove('d-none');
            }
            if (!modalEl.dataset.previewBound) {
                modalEl.dataset.previewBound = '1';
                modalEl.addEventListener('hidden.bs.modal', function () {
                    frame.src = 'about:blank';
                    if (loading) {
                        loading.classList.add('d-none');
                    }
                });
                frame.addEventListener('load', function () {
                    const src = frame.getAttribute('src') || '';
                    if (!src || src === 'about:blank') {
                        return;
                    }
                    if (loading) {
                        loading.classList.add('d-none');
                    }
                    try {
                        const loc = frame.contentWindow.location;
                        if (!loc || !loc.href || loc.href === 'about:blank') {
                            return;
                        }
                        const tab = new URL(loc.href);
                        tab.searchParams.delete('in_modal');
                        tab.searchParams.delete('inModal');
                        if (newTab) {
                            newTab.href = tab.href;
                        }
                        const titleEl = document.getElementById('leadViewModalLabel');
                        const docTitle = frame.contentDocument && frame.contentDocument.title;
                        if (titleEl && docTitle) {
                            titleEl.textContent = docTitle.replace(/\s*\|\s*[^|]+$/, '');
                        }
                    } catch (e) {}
                });
            }
            if (frame.getAttribute('src') !== urls.modal) {
                frame.src = urls.modal;
            } else if (loading) {
                loading.classList.add('d-none');
            }
            bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false }).show();
        };

        if (!window.__recordPreviewClickBound) {
            window.__recordPreviewClickBound = true;
            document.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button === 1) {
                    return;
                }
                const link = event.target.closest && event.target.closest('a.js-record-preview');
                if (link) {
                    event.preventDefault();
                    event.stopPropagation();
                    window.openRecordPreview(link.getAttribute('href'));
                    return;
                }
                const row = event.target.closest && event.target.closest('tr.js-record-preview-row');
                if (!row || (event.target.closest && event.target.closest('a, button, input, select, textarea, label, audio'))) {
                    return;
                }
                const rowUrl = row.getAttribute('data-preview-url');
                if (!rowUrl) {
                    return;
                }
                event.preventDefault();
                window.openRecordPreview(rowUrl);
            }, true);
        }
    })();
</script>
