@php
    $wfEntityType = $workflowContext['entity_type'] ?? ($wfEntityType ?? 'lead');
    $wfEntityId = (int) ($workflowContext['entity_id'] ?? ($wfEntityId ?? 0));
    $wfHasScenario = !empty($workflowContext['scenario']);
@endphp
@if($wfEntityId > 0)
<script>
(function () {
    let workflowFabDocBound = false;

    function initWorkflowFab() {
        const root = document.getElementById('workflow-fab-root');
        const trigger = document.getElementById('workflow-fab-trigger');
        const closeBtn = document.getElementById('workflow-fab-close');
        const panel = document.getElementById('workflow-next-step-card');
        if (!root || !trigger || !panel || root.dataset.initialized === '1') return;

        root.dataset.initialized = '1';

        function setOpen(open) {
            root.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(!root.classList.contains('is-open'));
        });

        closeBtn?.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(false);
        });

        if (!workflowFabDocBound) {
            workflowFabDocBound = true;
            document.addEventListener('click', function (e) {
                const fab = document.getElementById('workflow-fab-root');
                if (!fab || !fab.classList.contains('is-open')) return;
                if (fab.contains(e.target)) return;
                fab.classList.remove('is-open');
                document.getElementById('workflow-fab-trigger')?.setAttribute('aria-expanded', 'false');
                document.getElementById('workflow-next-step-card')?.setAttribute('aria-hidden', 'true');
            });
            document.addEventListener('keydown', function (e) {
                const fab = document.getElementById('workflow-fab-root');
                if (e.key === 'Escape' && fab?.classList.contains('is-open')) {
                    fab.classList.remove('is-open');
                    document.getElementById('workflow-fab-trigger')?.setAttribute('aria-expanded', 'false');
                    document.getElementById('workflow-next-step-card')?.setAttribute('aria-hidden', 'true');
                }
            });
        }
    }

    @if($wfHasScenario)
    initWorkflowFab();
    @endif

    const entityType = @json($wfEntityType);
    const entityId = @json($wfEntityId);
    const toggleUrl = @json(route('admin.workflow.steps.toggle'));
    const confirmBulkUrl = @json(route('admin.workflow.steps.confirm-bulk'));
    const checkGateUrl = @json(route('admin.workflow.check-gate'));
    const token = @json(csrf_token());

    function refreshWorkflowFab(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const next = doc.getElementById('workflow-fab-root');
        const current = document.getElementById('workflow-fab-root');
        if (!next || !current) return;
        const wasOpen = current.classList.contains('is-open');
        current.replaceWith(next);
        if (wasOpen) {
            document.getElementById('workflow-fab-root')?.classList.add('is-open');
            document.getElementById('workflow-fab-trigger')?.setAttribute('aria-expanded', 'true');
            document.getElementById('workflow-next-step-card')?.setAttribute('aria-hidden', 'false');
        }
        initWorkflowFab();
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.workflow-step-toggle');
        if (!btn) return;
        const cardEl = btn.closest('#workflow-next-step-card');
        if (!cardEl) return;
        const stepKey = btn.dataset.stepKey;
        const li = btn.closest('.workflow-step-item');
        const isDone = li?.dataset.isDone === '1';

        fetch(toggleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                entity_type: entityType,
                entity_id: entityId,
                step_key: stepKey,
                is_done: !isDone,
            }),
        }).then(function (r) { return r.json(); }).then(function () {
            fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.text(); })
                .then(refreshWorkflowFab);
        }).catch(function () {
            window.toastr && toastr.error(@json(translate('Failed_to_update')));
        });
    });

    window.WorkflowGate = {
        entityType: entityType,
        entityId: entityId,
        checkGateUrl: checkGateUrl,
        confirmBulkUrl: confirmBulkUrl,
        token: token,
        pendingProceed: null,

        check: function (action, onAllowed, confirmed) {
            const self = this;
            fetch(checkGateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    entity_type: entityType,
                    entity_id: entityId,
                    action: action,
                    confirmed: !!confirmed,
                }),
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.allowed) {
                    onAllowed();
                    return;
                }
                self.showConfirmModal(data, action, onAllowed);
            });
        },

        showConfirmModal: function (data, action, onAllowed, mode) {
            const modalEl = document.getElementById('workflowConfirmModal');
            if (!modalEl || !window.bootstrap?.Modal) {
                if ((data.hard_pending || []).length) {
                    window.toastr && toastr.error(data.message || 'Complete required steps first');
                } else if (confirm(data.message || 'Continue?')) {
                    onAllowed();
                }
                return;
            }

            const stepsEl = document.getElementById('workflow-confirm-steps');
            const hardNotice = document.getElementById('workflow-confirm-hard-notice');
            const proceedBtn = document.getElementById('workflow-confirm-proceed');
            const introPre = document.getElementById('workflow-confirm-intro');
            const introPost = document.getElementById('workflow-confirm-intro-post');
            const isPost = mode === 'post';
            if (introPre) introPre.classList.toggle('d-none', isPost);
            if (introPost) introPost.classList.toggle('d-none', !isPost);

            stepsEl.innerHTML = '';
            const pending = data.pending || [];
            const hasHard = (data.hard_pending || []).length > 0;

            pending.forEach(function (p) {
                const li = document.createElement('li');
                li.className = 'mb-2 small';
                li.innerHTML = '<label class="d-flex gap-2 align-items-start"><input type="checkbox" class="workflow-confirm-check mt-1" value="' + p.key + '" ' + (p.hard ? 'data-hard="1"' : '') + '> <span><strong>' + p.label + '</strong><br><span class="text-muted">' + (p.detail || '') + '</span></span></label>';
                stepsEl.appendChild(li);
            });

            if (hasHard) {
                hardNotice.textContent = data.message || '';
                hardNotice.classList.remove('d-none');
                proceedBtn.disabled = true;
            } else {
                hardNotice.classList.add('d-none');
                proceedBtn.disabled = false;
            }

            this.pendingProceed = { action: action, onAllowed: onAllowed, pending: pending, hasHard: hasHard, mode: mode || 'pre' };
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        },

        submitFormWithConfirmation: function (form, action) {
            this.check(action, function () {
                window.WorkflowGate.submitFormConfirmed(form);
            });
        },

        submitFormConfirmed: function (form) {
            let field = form.querySelector('input[name="workflow_confirmed"]');
            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = 'workflow_confirmed';
                form.appendChild(field);
            }
            field.value = '1';
            form.submit();
        },

        openModalWithConfirmation: function (modalSelector, action) {
            this.check(action, function () {
                const modalEl = document.querySelector(modalSelector);
                if (modalEl && window.bootstrap?.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
        },
    };

    document.getElementById('workflow-confirm-proceed')?.addEventListener('click', function () {
        const ctx = window.WorkflowGate.pendingProceed;
        if (!ctx) return;
        const keys = Array.from(document.querySelectorAll('.workflow-confirm-check:checked')).map(function (c) { return c.value; });
        const softKeys = (ctx.pending || []).filter(function (p) { return !p.hard; }).map(function (p) { return p.key; });
        const toMark = keys.length ? keys : softKeys;

        fetch(confirmBulkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                entity_type: entityType,
                entity_id: entityId,
                step_keys: toMark,
                action: ctx.action,
            }),
        }).finally(function () {
            bootstrap.Modal.getInstance(document.getElementById('workflowConfirmModal'))?.hide();
            ctx.onAllowed();
        });
    });
})();
</script>
@endif
