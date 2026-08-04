@php
    $wfEntityType = $workflowContext['entity_type'] ?? ($wfEntityType ?? 'lead');
    $wfEntityId = $workflowContext['entity_id'] ?? ($wfEntityId ?? null);
    $wfEntityReady = $wfEntityType === 'booking'
        ? filled($wfEntityId)
        : ((int) $wfEntityId) > 0;
    $wfHasScenario = !empty($workflowContext['scenario']);
@endphp
@if($wfEntityReady)
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());

    function closeWorkflowConfirmModal(onDone) {
        const modalEl = document.getElementById('workflowConfirmModal');
        const runNext = function () {
            if (modalEl) {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
            }
            document.querySelectorAll('.modal-backdrop').forEach(function (el) {
                el.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            if (typeof onDone === 'function') {
                onDone();
            }
        };

        if (!modalEl || !window.bootstrap?.Modal) {
            runNext();
            return;
        }

        const inst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        let finished = false;
        const finishOnce = function () {
            if (finished) return;
            finished = true;
            runNext();
        };

        modalEl.addEventListener('hidden.bs.modal', finishOnce, { once: true });
        inst.hide();
        window.setTimeout(finishOnce, 200);
    }

    function runWorkflowProceedCallback(onAllowed) {
        closeWorkflowConfirmModal(function () {
            if (typeof onAllowed === 'function') {
                onAllowed();
            }
        });
    }

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
                'X-CSRF-TOKEN': csrfToken,
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
        token: csrfToken,
        pendingProceed: null,

        check: function (action, onAllowed, confirmed) {
            const self = this;
            fetch(checkGateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    entity_type: entityType,
                    entity_id: entityId,
                    action: action,
                    confirmed: !!confirmed,
                }),
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data && data.allowed === true) {
                    onAllowed();
                    return;
                }
                if (!data || (!Array.isArray(data.pending) && !Array.isArray(data.hard_pending))) {
                    window.toastr && toastr.error((data && data.message) ? data.message : @json(translate('Something went wrong. Please try again.')));
                    return;
                }
                self.showConfirmModal(data, action, onAllowed);
            }).catch(function () {
                window.toastr && toastr.error(@json(translate('Something went wrong. Please try again.')));
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
            const pending = Array.isArray(data.pending) ? data.pending : [];
            const hardPending = Array.isArray(data.hard_pending) ? data.hard_pending : [];
            const hardKeys = new Set(hardPending.map(function (p) { return p.key; }));
            const stepsToShow = pending.length ? pending : hardPending;
            const hasHard = hardPending.length > 0;

            if (stepsToShow.length === 0 && data.message) {
                const li = document.createElement('li');
                li.className = 'mb-0 small text-muted';
                li.textContent = data.message;
                stepsEl.appendChild(li);
            }

            stepsToShow.forEach(function (p) {
                const isHard = !!p.hard || hardKeys.has(p.key);
                const li = document.createElement('li');
                li.className = 'mb-2 small';
                li.innerHTML = '<label class="d-flex gap-2 align-items-start' + (isHard ? '' : '') + '">' +
                    (isHard
                        ? '<span class="material-icons text-warning mt-1" style="font-size:18px;">error_outline</span>'
                        : '<input type="checkbox" class="workflow-confirm-check mt-1" value="' + p.key + '">') +
                    ' <span><strong>' + (p.label || p.key) + '</strong>' +
                    (p.detail ? '<br><span class="text-muted">' + p.detail + '</span>' : '') +
                    '</span></label>';
                stepsEl.appendChild(li);
            });

            function syncWorkflowProceedButton() {
                if (!proceedBtn || hasHard) {
                    return;
                }
                const softSteps = stepsToShow.filter(function (p) { return !p.hard && !hardKeys.has(p.key); });
                if (softSteps.length === 0) {
                    proceedBtn.disabled = false;
                    return;
                }
                const checked = stepsEl.querySelectorAll('.workflow-confirm-check:checked').length;
                proceedBtn.disabled = checked < softSteps.length;
            }

            stepsEl.querySelectorAll('.workflow-confirm-check').forEach(function (box) {
                box.addEventListener('change', syncWorkflowProceedButton);
            });
            syncWorkflowProceedButton();

            if (hasHard) {
                hardNotice.textContent = data.message || @json(translate('Complete_required_workflow_steps_first'));
                hardNotice.classList.remove('d-none');
                proceedBtn.disabled = true;
            } else {
                hardNotice.classList.add('d-none');
            }

            this.pendingProceed = { action: action, onAllowed: onAllowed, pending: stepsToShow, hasHard: hasHard, mode: mode || 'pre' };
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

    function handleWorkflowConfirmProceedClick(e) {
        const proceedBtn = e.target.closest('#workflow-confirm-proceed');
        if (!proceedBtn || proceedBtn.disabled) {
            return;
        }

        const ctx = window.WorkflowGate?.pendingProceed;
        if (!ctx || ctx.hasHard) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        proceedBtn.disabled = true;

        const keys = Array.from(document.querySelectorAll('.workflow-confirm-check:checked')).map(function (c) { return c.value; });
        if (!keys.length) {
            proceedBtn.disabled = false;
            return;
        }

        const finish = function () {
            runWorkflowProceedCallback(ctx.onAllowed);
        };

        fetch(confirmBulkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                entity_type: entityType,
                entity_id: entityId,
                step_keys: keys,
                action: ctx.action,
            }),
        }).then(function (r) {
            if (!r.ok) {
                throw new Error('confirm failed');
            }
            return r.json();
        }).then(finish).catch(function () {
            proceedBtn.disabled = false;
            window.toastr && toastr.error(@json(translate('Failed_to_update')));
        });
    }

    document.addEventListener('click', handleWorkflowConfirmProceedClick);
})();
</script>
@endif
