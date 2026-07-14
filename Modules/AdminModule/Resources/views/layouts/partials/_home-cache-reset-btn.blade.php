@php
    $homeCacheBtnClass = $buttonClass ?? 'top-utility-action-btn';
    $homeCacheLabelClass = $labelClass ?? 'top-utility-search-label d-none d-lg-inline';
    $homeCacheNeedsReset = $homeCacheNeedsReset ?? false;
    $homeCacheWrapClass = trim(($wrapperClass ?? 'home-cache-reset-wrap').($homeCacheNeedsReset ? ' home-cache-reset-wrap--stale' : ''));
@endphp

<div class="{{ $homeCacheWrapClass }}">
    @if($homeCacheNeedsReset)
        <span class="js-home-cache-reset-reminder home-cache-reset-reminder {{ $reminderClass ?? 'd-none d-md-inline' }}"
              title="{{ translate('Home_content_changed_reset_cache_reminder') }}">
            <span class="material-symbols-outlined home-cache-reset-reminder-icon" aria-hidden="true">info</span>
            <span class="home-cache-reset-reminder-text">{{ translate('Home_content_changed_reset_cache_reminder') }}</span>
        </span>
    @endif

    <div class="home-cache-reset-control">
        <form method="POST"
              action="{{ route('admin.customer.home-cache.reset') }}"
              class="js-home-cache-reset-form {{ $formClass ?? 'top-utility-item d-inline' }}">
            @csrf
            <button type="submit"
                    class="js-home-cache-reset-btn {{ $homeCacheBtnClass }}{{ $homeCacheNeedsReset ? ' home-cache-reset-btn--attention' : '' }}"
                    data-bs-toggle="tooltip"
                    data-bs-placement="bottom"
                    data-default-label="{{ translate('Reset_home_cache') }}"
                    data-loading-label="{{ translate('Rebuilding_home_cache') }}"
                    title="{{ $homeCacheNeedsReset ? translate('Home_content_changed_reset_cache_reminder') : translate('Reset_and_rebuild_customer_home_cache') }}">
                <span class="material-symbols-outlined js-home-cache-reset-icon">cached</span>
                <span class="js-home-cache-reset-label {{ $homeCacheLabelClass }}">{{ translate('Reset_home_cache') }}</span>
            </button>
        </form>

        <div class="js-home-cache-progress home-cache-progress d-none" aria-live="polite">
            <div class="home-cache-progress-meta">
                <span class="js-home-cache-progress-label home-cache-progress-label">{{ translate('Rebuilding_home_cache') }}</span>
                <span class="js-home-cache-progress-percent home-cache-progress-percent">0%</span>
            </div>
            <div class="home-cache-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <div class="js-home-cache-progress-bar home-cache-progress-bar" style="width: 0%;"></div>
            </div>
        </div>
    </div>
</div>

@once
    @push('css_or_js')
        <style>
            .home-cache-reset-wrap {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                max-width: 100%;
                flex-shrink: 1;
                min-width: 0;
            }

            .home-cache-reset-control {
                display: inline-flex;
                align-items: center;
                min-width: 0;
            }

            .home-cache-reset-reminder {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.35rem 0.6rem;
                border-radius: 999px;
                background: #fff4e5;
                color: #9a5b00;
                border: 1px solid #ffd591;
                font-size: 0.75rem;
                font-weight: 600;
                line-height: 1.2;
                white-space: nowrap;
            }

            .home-cache-reset-reminder-icon {
                font-size: 1rem;
                line-height: 1;
            }

            .home-cache-reset-wrap--stale .home-cache-reset-btn--attention {
                animation: home-cache-reset-pulse 1.15s ease-in-out infinite;
                box-shadow: 0 0 0 0 rgba(255, 153, 0, 0.55);
            }

            .home-cache-progress {
                width: min(180px, 36vw);
                padding: 0.3rem 0.45rem 0.35rem;
                border-radius: 0.5rem;
                background: rgba(15, 23, 42, 0.04);
                border: 1px solid rgba(15, 23, 42, 0.08);
                flex-shrink: 0;
            }

            .home-cache-progress-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                margin-bottom: 0.2rem;
            }

            .home-cache-progress-label,
            .home-cache-progress-percent {
                font-size: 0.7rem;
                font-weight: 600;
                line-height: 1.2;
                color: #475569;
                white-space: nowrap;
            }

            .home-cache-progress-track {
                width: 100%;
                height: 0.35rem;
                overflow: hidden;
                border-radius: 999px;
                background: #e2e8f0;
            }

            .home-cache-progress-bar {
                height: 100%;
                width: 0;
                border-radius: inherit;
                background: linear-gradient(90deg, #0ea5e9, #0284c7);
                transition: width 0.35s ease;
            }

            .home-cache-alert {
                width: 100%;
                background: #fef2f2;
                border-bottom: 1px solid #fecaca;
                color: #991b1b;
            }

            .home-cache-alert-inner {
                display: flex;
                align-items: flex-start;
                gap: 0.65rem;
                max-width: 1400px;
                margin: 0 auto;
                padding: 0.7rem 1rem;
            }

            .home-cache-alert-icon {
                flex-shrink: 0;
                font-size: 1.25rem;
                line-height: 1.3;
                color: #dc2626;
            }

            .home-cache-alert-message {
                flex: 1;
                min-width: 0;
                font-size: 0.875rem;
                font-weight: 600;
                line-height: 1.4;
                word-break: break-word;
            }

            .home-cache-alert-dismiss {
                flex-shrink: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.75rem;
                height: 1.75rem;
                margin: -0.15rem -0.25rem 0 0;
                padding: 0;
                border: 0;
                border-radius: 0.35rem;
                background: transparent;
                color: #991b1b;
                cursor: pointer;
            }

            .home-cache-alert-dismiss:hover,
            .home-cache-alert-dismiss:focus-visible {
                background: rgba(185, 28, 28, 0.1);
                outline: none;
            }

            .home-cache-alert-dismiss .material-symbols-outlined {
                font-size: 1.15rem;
                line-height: 1;
            }

            @keyframes home-cache-reset-pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(255, 153, 0, 0.45);
                }
                70% {
                    box-shadow: 0 0 0 8px rgba(255, 153, 0, 0);
                }
                100% {
                    box-shadow: 0 0 0 0 rgba(255, 153, 0, 0);
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            (function () {
                const POLL_INTERVAL_MS = 800;
                const POLL_TIMEOUT_MS = 5 * 60 * 1000;
                // Sync warm (php artisan serve) can take several minutes across zones/locales.
                const REQUEST_TIMEOUT_MS = 5 * 60 * 1000;
                const SOFT_TICK_MS = 200;
                const labels = {
                    success: @json(translate('Home_cache_reset_and_warmed_successfully')),
                    queued: @json(translate('Home_cache_reset_rebuild_queued')),
                    queuedRefresh: @json(translate('Home_cache_reset_rebuild_queued_refresh')),
                    timeout: @json(translate('Home_cache_reset_rebuild_timeout')),
                    requestTimeout: @json(translate('Home_cache_reset_request_timeout')),
                    failed: @json(translate('Failed_to_rebuild_home_cache')),
                    rebuilding: @json(translate('Rebuilding_home_cache')),
                    sessionExpired: 'Session expired. Please refresh the page and try again.',
                    serverError: 'Server returned an unexpected response. Please refresh and try again.',
                };

                function getAlertRoot() {
                    return document.getElementById('js-home-cache-alert');
                }

                function hideHomeCacheAlert() {
                    const root = getAlertRoot();
                    if (!root) {
                        return;
                    }
                    root.classList.add('d-none');
                    root.setAttribute('hidden', 'hidden');
                    const messageEl = root.querySelector('.js-home-cache-alert-message');
                    if (messageEl) {
                        messageEl.textContent = '';
                    }
                }

                function showHomeCacheAlert(message) {
                    const root = getAlertRoot();
                    if (!root) {
                        return;
                    }
                    const messageEl = root.querySelector('.js-home-cache-alert-message');
                    if (messageEl) {
                        messageEl.textContent = message || labels.failed;
                    }
                    root.classList.remove('d-none');
                    root.removeAttribute('hidden');
                }

                function getProgressRoot(button) {
                    const wrap = button.closest('.home-cache-reset-wrap');
                    return wrap ? wrap.querySelector('.js-home-cache-progress') : null;
                }

                function setProgressUI(button, options) {
                    const root = getProgressRoot(button);
                    if (!root) {
                        return;
                    }

                    const labelEl = root.querySelector('.js-home-cache-progress-label');
                    const percentEl = root.querySelector('.js-home-cache-progress-percent');
                    const barEl = root.querySelector('.js-home-cache-progress-bar');
                    const trackEl = root.querySelector('.home-cache-progress-track');
                    const percent = Math.max(0, Math.min(100, Math.round(Number(options.percent) || 0)));

                    root.classList.toggle('d-none', !options.visible);

                    if (labelEl) {
                        labelEl.textContent = options.label || labels.rebuilding;
                    }
                    if (percentEl) {
                        percentEl.textContent = percent + '%';
                    }
                    if (barEl) {
                        barEl.style.width = percent + '%';
                    }
                    if (trackEl) {
                        trackEl.setAttribute('aria-valuenow', String(percent));
                    }
                }

                function createProgressTracker(button) {
                    return {
                        button: button,
                        serverPercent: 1,
                        displayPercent: 1,
                        total: 0,
                        done: 0,
                        startedAt: Date.now(),
                        softTimer: null,
                        active: false,
                        render: function () {
                            if (!this.active) {
                                return;
                            }
                            setProgressUI(this.button, {
                                visible: true,
                                percent: this.displayPercent,
                                label: labels.rebuilding,
                            });
                        },
                        softEstimate: function () {
                            const elapsedMs = Date.now() - this.startedAt;
                            // Assume ~1.2s per cache unit; never claim more than 95% until the server finishes.
                            const units = Math.max(1, this.total || 8);
                            const expectedMs = units * 1200;
                            return Math.min(95, (elapsedMs / expectedMs) * 100);
                        },
                        tick: function () {
                            if (!this.active) {
                                return;
                            }
                            const floor = Math.max(this.serverPercent, 1);
                            const soft = this.softEstimate();
                            const target = Math.min(95, Math.max(floor, soft));
                            // Ease toward target so the bar never jumps or freezes.
                            if (target > this.displayPercent) {
                                this.displayPercent = Math.min(target, this.displayPercent + Math.max(0.4, (target - this.displayPercent) * 0.22));
                            }
                            this.render();
                        },
                        start: function (rebuild) {
                            this.active = true;
                            this.startedAt = Date.now();
                            this.serverPercent = 1;
                            this.displayPercent = 1;
                            if (rebuild) {
                                this.applyServer(rebuild);
                            }
                            this.render();
                            const self = this;
                            this.stopSoft();
                            this.softTimer = setInterval(function () {
                                self.tick();
                            }, SOFT_TICK_MS);
                        },
                        applyServer: function (rebuild) {
                            if (!rebuild) {
                                return;
                            }
                            if (typeof rebuild.total === 'number' && rebuild.total > 0) {
                                this.total = rebuild.total;
                            }
                            if (typeof rebuild.done === 'number') {
                                this.done = rebuild.done;
                            }
                            if (typeof rebuild.percent === 'number') {
                                this.serverPercent = Math.max(1, Math.min(99, rebuild.percent));
                            }
                            if (typeof rebuild.started_at === 'number' && rebuild.started_at > 0) {
                                this.startedAt = rebuild.started_at * 1000;
                            }
                            this.displayPercent = Math.max(this.displayPercent, this.serverPercent);
                            this.render();
                        },
                        complete: function () {
                            this.stopSoft();
                            this.active = false;
                            this.displayPercent = 100;
                            setProgressUI(this.button, {
                                visible: true,
                                percent: 100,
                                label: labels.success,
                            });
                        },
                        hide: function () {
                            this.stopSoft();
                            this.active = false;
                            setProgressUI(this.button, {
                                visible: false,
                                percent: 0,
                                label: labels.rebuilding,
                            });
                        },
                        stopSoft: function () {
                            if (this.softTimer) {
                                clearInterval(this.softTimer);
                                this.softTimer = null;
                            }
                        },
                    };
                }

                function setHomeCacheButtonLoading(button, loading, tracker) {
                    const label = button.querySelector('.js-home-cache-reset-label');
                    const icon = button.querySelector('.js-home-cache-reset-icon');
                    const defaultLabel = button.dataset.defaultLabel || 'Reset home cache';

                    button.disabled = loading;
                    button.classList.toggle('disabled', loading);
                    button.classList.toggle('d-none', loading);
                    button.setAttribute('aria-busy', loading ? 'true' : 'false');

                    if (icon) {
                        icon.classList.toggle('d-none', false);
                    }
                    if (label) {
                        label.textContent = defaultLabel;
                    }

                    if (loading && tracker) {
                        hideHomeCacheAlert();
                        tracker.start();
                    }
                }

                function clearHomeCacheReminder() {
                    document.querySelectorAll('.home-cache-reset-wrap--stale').forEach(function (wrap) {
                        wrap.classList.remove('home-cache-reset-wrap--stale');
                    });
                    document.querySelectorAll('.js-home-cache-reset-reminder').forEach(function (el) {
                        el.remove();
                    });
                    document.querySelectorAll('.home-cache-reset-btn--attention').forEach(function (btn) {
                        btn.classList.remove('home-cache-reset-btn--attention');
                    });
                }

                function showHomeCacheToast(message, type) {
                    if (type === 'error') {
                        // Errors use the persistent banner below the header — not auto-hiding toasts.
                        showHomeCacheAlert(message);
                        return;
                    }
                    const toastType = type === 'warning' ? 'warning' : type;
                    if (window.toastr && typeof window.toastr[toastType] === 'function') {
                        window.toastr[toastType](message);
                        return;
                    }
                    console.log(message);
                }

                function fetchWithTimeout(url, options, timeoutMs) {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(function () {
                        controller.abort();
                    }, timeoutMs);

                    return fetch(url, Object.assign({}, options, { signal: controller.signal }))
                        .finally(function () {
                            clearTimeout(timeoutId);
                        });
                }

                function parseJsonResponse(response) {
                    return response.text().then(function (text) {
                        let data = null;

                        if (text) {
                            try {
                                data = JSON.parse(text);
                            } catch (error) {
                                data = null;
                            }
                        }

                        if (!response.ok) {
                            let message = data && data.message ? data.message : '';

                            if (!message && data && data.rebuild && data.rebuild.error) {
                                message = data.rebuild.error;
                            }

                            if (!message && response.status === 419) {
                                message = labels.sessionExpired;
                            }

                            if (!message && text && text.indexOf('<') !== -1) {
                                message = labels.serverError;
                            }

                            if (!message) {
                                message = response.status >= 500 ? labels.serverError : labels.failed;
                            }

                            const err = new Error(message);
                            err.rebuild = data && data.rebuild ? data.rebuild : null;
                            throw err;
                        }

                        if (!data) {
                            throw new Error(labels.serverError);
                        }

                        if (data.success === false) {
                            const err = new Error(data.message || labels.failed);
                            err.rebuild = data.rebuild || null;
                            throw err;
                        }

                        return data;
                    });
                }

                function pollHomeCacheStatus(tracker, resetUrl, csrf) {
                    const startedAt = Date.now();

                    return new Promise(function (resolve, reject) {
                        function check() {
                            if (Date.now() - startedAt > POLL_TIMEOUT_MS) {
                                reject(new Error(labels.timeout));
                                return;
                            }

                            const statusFormData = new FormData();
                            statusFormData.append('_token', csrf);
                            statusFormData.append('check_only', '1');

                            fetchWithTimeout(resetUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrf,
                                },
                                body: statusFormData,
                                credentials: 'same-origin',
                            }, REQUEST_TIMEOUT_MS)
                                .then(parseJsonResponse)
                                .then(function (data) {
                                    tracker.applyServer(data.rebuild);

                                    if (data.rebuild && data.rebuild.status === 'failed') {
                                        reject(new Error(data.rebuild.error || labels.failed));
                                        return;
                                    }

                                    if (data.needs_reset === false || (data.rebuild && data.rebuild.status === 'complete')) {
                                        tracker.complete();
                                        resolve(data);
                                        return;
                                    }

                                    setTimeout(check, POLL_INTERVAL_MS);
                                })
                                .catch(function (error) {
                                    reject(error);
                                });
                        }

                        check();
                    });
                }

                function restoreResetButton(button) {
                    button.disabled = false;
                    button.classList.remove('disabled', 'd-none');
                    button.setAttribute('aria-busy', 'false');
                }

                function finishResetUi(button, tracker, options) {
                    const failed = !!options.failed;
                    const message = options.message || '';

                    if (failed) {
                        tracker.hide();
                        restoreResetButton(button);
                        showHomeCacheAlert(message || labels.failed);
                        return;
                    }

                    hideHomeCacheAlert();
                    tracker.complete();
                    setTimeout(function () {
                        setProgressUI(button, {
                            visible: false,
                            percent: 0,
                            label: labels.rebuilding,
                        });
                        restoreResetButton(button);
                    }, 900);
                }

                document.addEventListener('click', function (event) {
                    const dismissBtn = event.target.closest('.js-home-cache-alert-dismiss');
                    if (!dismissBtn) {
                        return;
                    }
                    hideHomeCacheAlert();
                });

                document.addEventListener('submit', function (event) {
                    const form = event.target.closest('.js-home-cache-reset-form');
                    if (!form) {
                        return;
                    }

                    event.preventDefault();

                    const button = form.querySelector('.js-home-cache-reset-btn');
                    if (!button || button.disabled) {
                        return;
                    }

                    const tokenInput = form.querySelector('input[name="_token"]');
                    const csrf = tokenInput ? tokenInput.value : '';
                    const formData = new FormData(form);
                    const tracker = createProgressTracker(button);

                    setHomeCacheButtonLoading(button, true, tracker);

                    fetchWithTimeout(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: formData,
                        credentials: 'same-origin',
                    }, REQUEST_TIMEOUT_MS)
                        .then(parseJsonResponse)
                        .then(function (data) {
                            tracker.applyServer(data.rebuild);

                            const finishedNow = data.needs_reset === false
                                || (data.rebuild && data.rebuild.status === 'complete')
                                || (typeof data.warmed === 'number' && data.warmed > 0);

                            if (finishedNow) {
                                clearHomeCacheReminder();
                                showHomeCacheToast(data.message || labels.success, 'success');
                                finishResetUi(button, tracker, { failed: false });
                                return;
                            }

                            if (data.queued) {
                                return pollHomeCacheStatus(tracker, form.action, csrf).then(function () {
                                    clearHomeCacheReminder();
                                    showHomeCacheToast(labels.success, 'success');
                                    finishResetUi(button, tracker, { failed: false });
                                }).catch(function (pollError) {
                                    const message = pollError.message || labels.queuedRefresh;
                                    finishResetUi(button, tracker, { failed: true, message: message });
                                });
                            }

                            clearHomeCacheReminder();
                            showHomeCacheToast(data.message || labels.success, 'success');
                            finishResetUi(button, tracker, { failed: false });
                        })
                        .catch(function (error) {
                            const message = error.name === 'AbortError'
                                ? labels.requestTimeout
                                : (error.message || labels.failed);

                            if (error.rebuild) {
                                tracker.applyServer(error.rebuild);
                            }

                            finishResetUi(button, tracker, { failed: true, message: message });
                        });
                });
            })();
        </script>
    @endpush
@endonce
