@php
    $homeCacheBtnClass = $buttonClass ?? 'top-utility-action-btn';
    $homeCacheLabelClass = $labelClass ?? 'top-utility-search-label d-none d-lg-inline';
@endphp

<form method="POST"
      action="{{ route('admin.customer.home-cache.reset') }}"
      class="js-home-cache-reset-form {{ $formClass ?? 'top-utility-item d-inline' }}">
    @csrf
    <button type="submit"
            class="js-home-cache-reset-btn {{ $homeCacheBtnClass }}"
            data-bs-toggle="tooltip"
            data-bs-placement="bottom"
            data-default-label="{{ translate('Reset_home_cache') }}"
            data-loading-label="{{ translate('Rebuilding_home_cache') }}"
            title="{{ translate('Reset_and_rebuild_customer_home_cache') }}">
        <span class="material-symbols-outlined js-home-cache-reset-icon">cached</span>
        <span class="js-home-cache-reset-label {{ $homeCacheLabelClass }}">{{ translate('Reset_home_cache') }}</span>
        <span class="spinner-border spinner-border-sm js-home-cache-reset-spinner d-none" role="status" aria-hidden="true"></span>
    </button>
</form>

@once
    @push('script')
        <script>
            (function () {
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

                    setHomeCacheButtonLoading(button, true);

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: formData,
                        credentials: 'same-origin',
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw new Error(data.message || 'Request failed');
                                }
                                return data;
                            });
                        })
                        .then(function (data) {
                            showHomeCacheToast(data.message || 'Home cache rebuilt successfully.', 'success');
                        })
                        .catch(function (error) {
                            showHomeCacheToast(error.message || 'Failed to rebuild home cache.', 'error');
                        })
                        .finally(function () {
                            setHomeCacheButtonLoading(button, false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
