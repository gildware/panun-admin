@php
    use Modules\ProviderManagement\Services\ProviderManualPerformanceEnforcement;

    $suspensionItems = $provider
        ? (ProviderManualPerformanceEnforcement::summarize($provider)['items'] ?? [])
        : [];
    $unsuspendMethods = collect($suspensionItems)
        ->pluck('unsuspend_method')
        ->filter()
        ->unique()
        ->values()
        ->all();
@endphp

@if($provider && !empty($suspensionItems))
    @push('css_or_js')
        <style>
            .provider-suspension-alert {
                border-left: 4px solid #e74c3c;
            }

            .provider-suspension-alert__title {
                font-weight: 800;
                color: #c0392b;
            }

            .provider-suspension-alert__item + .provider-suspension-alert__item {
                border-top: 1px dashed rgba(231, 76, 60, .25);
                margin-top: .75rem;
                padding-top: .75rem;
            }
        </style>
    @endpush

    <div class="alert alert-danger provider-suspension-alert mb-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="flex-grow-1">
                <div class="provider-suspension-alert__title mb-2">
                    {{ translate('Account restrictions active') }}
                </div>

                @foreach($suspensionItems as $item)
                    <div class="provider-suspension-alert__item">
                        <div class="fw-bold mb-1">{{ $item['label'] }}</div>
                        <p class="mb-2 small text-dark">{{ $item['reason'] }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            @if(!empty($item['until']))
                                <span class="badge bg-danger">
                                    {{ translate('Until') }} {{ $item['until'] }}
                                </span>
                            @endif
                            @if($item['blocks_login'])
                                <span class="badge bg-dark">{{ translate('Blocks app login') }}</span>
                            @endif
                            @if($item['blocks_bookings'])
                                <span class="badge bg-warning text-dark">{{ translate('Blocks new bookings') }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @can('provider_manage_status')
                <div class="d-flex flex-column gap-2 flex-shrink-0">
                    @if(in_array('performance_active', $unsuspendMethods, true))
                        <form method="POST"
                              action="{{ route('admin.provider.provider-performance-status.update') }}"
                              class="provider-unsuspend-performance-form mb-0">
                            @csrf
                            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                            <input type="hidden" name="manual_status" value="active">
                            <button type="button"
                                    class="btn btn-success btn-sm provider-unsuspend-performance-btn"
                                    data-message="{{ translate('want_to_update_suspend') }}">
                                {{ translate('Account Unsuspend') }}
                            </button>
                        </form>
                    @endif

                    @if(in_array('cash_unsuspend', $unsuspendMethods, true))
                        <button type="button"
                                class="btn btn-outline-success btn-sm route-alert-reload"
                                data-route="{{ route('admin.provider.suspend_update', [$provider->id]) }}"
                                data-message="{{ translate('want_to_update_suspend') }}">
                            {{ translate('Remove cash suspension') }}
                        </button>
                    @endif

                    <a href="{{ route('admin.provider.details', [$provider->id, 'web_page' => 'performance']) }}"
                       class="btn btn-outline-secondary btn-sm text-center">
                        {{ translate('View performance') }}
                    </a>
                </div>
            @endcan
        </div>
    </div>

    @push('script')
        <script>
            "use strict";

            (function () {
                function bindProviderUnsuspendButtons(root) {
                    root = root || document.getElementById('admin-main') || document;

                    root.querySelectorAll('.provider-unsuspend-performance-btn').forEach(function (btn) {
                        if (btn.dataset.providerUnsuspendBound === '1') {
                            return;
                        }

                        btn.dataset.providerUnsuspendBound = '1';
                        btn.addEventListener('click', function () {
                            const form = btn.closest('form');
                            const message = btn.dataset.message || '{{ translate('are_you_sure') }}?';

                            if (typeof Swal === 'undefined') {
                                if (confirm(message)) {
                                    form.submit();
                                }
                                return;
                            }

                            Swal.fire({
                                title: "{{ translate('are_you_sure') }}?",
                                text: message,
                                type: 'warning',
                                showCancelButton: true,
                                cancelButtonColor: 'var(--bs-secondary)',
                                confirmButtonColor: 'var(--bs-primary)',
                                cancelButtonText: 'Cancel',
                                confirmButtonText: 'Yes',
                                reverseButtons: true
                            }).then(function (result) {
                                if (result.value && form) {
                                    form.submit();
                                }
                            });
                        });
                    });
                }

                bindProviderUnsuspendButtons(document.getElementById('admin-main') || document);

                document.addEventListener('admin:page-loaded', function (event) {
                    bindProviderUnsuspendButtons(
                        event.detail && event.detail.root ? event.detail.root : document
                    );
                });
            })();
        </script>
    @endpush
@endif
