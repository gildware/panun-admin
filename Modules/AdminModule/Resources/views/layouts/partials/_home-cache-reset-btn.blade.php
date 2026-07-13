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

    <form method="POST"
          action="{{ route('admin.customer.home-cache.reset') }}"
          data-status-url="{{ route('admin.customer.home-cache.status') }}"
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
            <span class="spinner-border spinner-border-sm js-home-cache-reset-spinner d-none" role="status" aria-hidden="true"></span>
        </button>
    </form>
</div>

@once
    @push('css_or_js')
        <style>
            .home-cache-reset-wrap {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                max-width: 100%;
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
                const POLL_INTERVAL_MS = 2000;
                const POLL_TIMEOUT_MS = 5 * 60 * 1000;
                const REQUEST_TIMEOUT_MS = 30000;

                function setHomeCacheButtonLoading(button, loading) {
                    const label = button.querySelector('.js-home-cache-reset-label');
                    const icon = button.querySelector('.js-home-cache-reset-icon');
                    const spinner = button.querySelector('.js-home-cache-reset-spinner');
                    const defaultLabel = button.dataset.defaultLabel || 'Reset home cache';
                    const loadingLabel = button.dataset.loadingLabel || 'Rebuilding...';

                    button.disabled = loading;
                    button.classList.toggle('disabled', loading);
                    button.setAttribute('aria-busy', loading ? 'true' : 'false');

                    if (icon) {
                        icon.classList.toggle('d-none', loading);
                    }
                    if (spinner) {
                        spinner.classList.toggle('d-none', !loading);
                    }
                    if (label) {
                        label.textContent = loading ? loadingLabel : defaultLabel;
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
                    if (window.toastr && typeof window.toastr[type] === 'function') {
                        window.toastr[type](message);
                        return;
                    }
                    if (type === 'error') {
                        console.error(message);
                    } else {
                        console.log(message);
                    }
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

                function pollHomeCacheStatus(statusUrl, csrf) {
                    const startedAt = Date.now();

                    return new Promise(function (resolve, reject) {
                        function check() {
                            if (Date.now() - startedAt > POLL_TIMEOUT_MS) {
                                reject(new Error('{{ translate('Home_cache_reset_rebuild_timeout') }}'));
                                return;
                            }

                            fetchWithTimeout(statusUrl, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrf,
                                },
                                credentials: 'same-origin',
                            }, REQUEST_TIMEOUT_MS)
                                .then(function (response) {
                                    return response.json().then(function (data) {
                                        if (!response.ok) {
                                            throw new Error(data.message || 'Status check failed');
                                        }
                                        return data;
                                    });
                                })
                                .then(function (data) {
                                    if (data.needs_reset === false) {
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
                    const statusUrl = form.dataset.statusUrl || '';

                    setHomeCacheButtonLoading(button, true);

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
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw new Error(data.message || 'Request failed');
                                }
                                return data;
                            });
                        })
                        .then(function (data) {
                            if (data.needs_reset === false) {
                                clearHomeCacheReminder();
                                showHomeCacheToast(data.message || 'Home cache rebuilt successfully.', 'success');
                                return;
                            }

                            if (!statusUrl) {
                                showHomeCacheToast(data.message || 'Home cache rebuild has been queued.', 'success');
                                return;
                            }

                            return pollHomeCacheStatus(statusUrl, csrf).then(function () {
                                clearHomeCacheReminder();
                                showHomeCacheToast('{{ translate('Home_cache_reset_and_warmed_successfully') }}', 'success');
                            });
                        })
                        .catch(function (error) {
                            const message = error.name === 'AbortError'
                                ? '{{ translate('Home_cache_reset_request_timeout') }}'
                                : (error.message || 'Failed to rebuild home cache.');
                            showHomeCacheToast(message, 'error');
                        })
                        .finally(function () {
                            setHomeCacheButtonLoading(button, false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
