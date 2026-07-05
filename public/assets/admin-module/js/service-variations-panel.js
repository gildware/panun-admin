(function () {
    'use strict';

    var PANEL_HEADER = 'X-Variations-Panel';
    var PANEL_VALUE = '1';

    function panelHeaders() {
        return {
            'Accept': 'text/html, application/json',
            'X-Requested-With': 'XMLHttpRequest',
            [PANEL_HEADER]: PANEL_VALUE,
        };
    }

    function jsonHeaders() {
        return {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            [PANEL_HEADER]: PANEL_VALUE,
        };
    }

    function getWorkspace() {
        return document.getElementById('service-variations-workspace');
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function toastSuccess(message) {
        if (message && typeof toastr !== 'undefined') {
            toastr.success(message);
        }
    }

    function toastError(message) {
        if (message && typeof toastr !== 'undefined') {
            toastr.error(message);
        } else if (message) {
            console.error(message);
        }
    }

    function setWorkspaceHtml(html) {
        var workspace = getWorkspace();
        if (!workspace) return;
        workspace.innerHTML = html;
        initPanel(workspace);
    }

    function initZonePricingToggle(root) {
        root.querySelectorAll('.js-variant-zone-pricing-toggle').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var panel = toggle.closest('.service-variations-panel');
                if (!panel) return;
                var enabled = toggle.checked;
                panel.querySelectorAll('.js-variant-zone-price-input').forEach(function (el) {
                    el.readOnly = !enabled;
                });
                var table = panel.querySelector('.js-variant-zone-price-table');
                if (table) {
                    table.classList.toggle('opacity-50', !enabled);
                }
            });
        });
    }

    function initPanel(root) {
        root = root || getWorkspace();
        if (!root) return;
        initZonePricingToggle(root);
    }

    function loadPanel(url) {
        var workspace = getWorkspace();
        if (!workspace || !url) return;

        workspace.classList.add('is-loading');

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: panelHeaders(),
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('load_failed');
                }
                return response.text();
            })
            .then(function (html) {
                setWorkspaceHtml(html);
            })
            .catch(function () {
                toastError('Failed to load');
            })
            .finally(function () {
                workspace.classList.remove('is-loading');
            });
    }

    function loadList() {
        var workspace = getWorkspace();
        if (!workspace) return;
        loadPanel(workspace.getAttribute('data-list-url'));
    }

    function submitPanelForm(form) {
        var workspace = getWorkspace();
        if (!workspace || !form) return;

        var formData = new FormData(form);
        var method = (form.querySelector('input[name="_method"]') || {}).value || form.method || 'POST';

        workspace.classList.add('is-loading');

        fetch(form.action, {
            method: method.toUpperCase() === 'GET' ? 'GET' : 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: formData,
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, status: response.status, data: data };
                }).catch(function () {
                    return { ok: response.ok, status: response.status, data: null };
                });
            })
            .then(function (result) {
                if (result.ok && result.data && result.data.html) {
                    setWorkspaceHtml(result.data.html);
                    toastSuccess(result.data.message);
                    return;
                }

                var message = (result.data && (result.data.message || result.data.error))
                    || (result.data && result.data.errors && Object.values(result.data.errors).flat().join(' '))
                    || 'Save failed';
                toastError(message);
            })
            .catch(function () {
                toastError('Save failed');
            })
            .finally(function () {
                workspace.classList.remove('is-loading');
            });
    }

    function deleteVariant(url, message) {
        var workspace = getWorkspace();
        if (!workspace || !url) return;

        var confirmDelete = function () {
            workspace.classList.add('is-loading');
            var body = new URLSearchParams();
            body.append('_token', getCsrfToken());
            body.append('_method', 'DELETE');

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(),
                body: body,
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (result.ok && result.data && result.data.html) {
                        setWorkspaceHtml(result.data.html);
                        toastSuccess(result.data.message);
                        return;
                    }
                    toastError((result.data && result.data.message) || 'Delete failed');
                })
                .catch(function () {
                    toastError('Delete failed');
                })
                .finally(function () {
                    workspace.classList.remove('is-loading');
                });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                type: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true,
            }).then(function (result) {
                if (result.value) {
                    confirmDelete();
                }
            });
        } else if (window.confirm(message)) {
            confirmDelete();
        }
    }

    document.addEventListener('click', function (event) {
        var panelUrlBtn = event.target.closest('[data-variations-panel-url]');
        if (panelUrlBtn && getWorkspace() && getWorkspace().contains(panelUrlBtn)) {
            event.preventDefault();
            loadPanel(panelUrlBtn.getAttribute('data-variations-panel-url'));
            return;
        }

        var backBtn = event.target.closest('.js-variations-panel-back');
        if (backBtn && getWorkspace() && getWorkspace().contains(backBtn)) {
            event.preventDefault();
            loadList();
            return;
        }

        var deleteBtn = event.target.closest('.js-variations-panel-delete');
        if (deleteBtn && getWorkspace() && getWorkspace().contains(deleteBtn)) {
            event.preventDefault();
            deleteVariant(
                deleteBtn.getAttribute('data-variations-panel-delete'),
                deleteBtn.getAttribute('data-message') || 'Delete this variation?'
            );
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('.js-variations-panel-form');
        if (form && getWorkspace() && getWorkspace().contains(form)) {
            event.preventDefault();
            submitPanelForm(form);
        }
    });

    document.addEventListener('DOMContentLoaded', initPanel);
    document.addEventListener('turbo:frame-load', initPanel);
    document.addEventListener('turbo:load', initPanel);

    if (document.readyState !== 'loading') {
        initPanel();
    }
})();
