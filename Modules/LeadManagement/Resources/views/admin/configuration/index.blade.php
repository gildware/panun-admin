@extends('adminmodule::layouts.new-master')

@section('title', translate('Lead_Configuration'))

@section('content')
    @php
        $leadConfigTabIds = [
            'general-tab-pane',
            'customer-tab-pane',
            'provider-tab-pane',
            'future-customer-tab-pane',
            'future-provider-tab-pane',
            'invalid-tab-pane',
        ];
        $leadConfigTab = (string) request('tab', 'general-tab-pane');
        if (! in_array($leadConfigTab, $leadConfigTabIds, true)) {
            $leadConfigTab = 'general-tab-pane';
        }
    @endphp
    <div class="main-content lead-config-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-2 d-flex justify-content-between flex-wrap align-items-center gap-2">
                        <h2 class="page-title mb-1">{{ translate('Lead_Configuration') }}</h2>
                    </div>

                    <div class="card card-body mb-2 py-2">
                        <label for="leadConfigSearchInput" class="form-label mb-1">{{ translate('Search here') }}</label>
                        <input type="search"
                               id="leadConfigSearchInput"
                               class="form-control"
                               placeholder="{{ translate('Search here') }}…"
                               autocomplete="off"
                               aria-describedby="leadConfigSearchHints">
                        <div id="leadConfigTabNavHits" class="d-flex flex-wrap gap-2 mt-2 d-none" role="group" aria-label="{{ translate('Pages') }}"></div>
                        <div id="leadConfigSearchHints" class="small text-muted mt-2 d-none"></div>
                    </div>

                    <ul class="nav nav--tabs mb-2" id="leadConfigTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $leadConfigTab === 'general-tab-pane' ? 'active' : '' }}"
                                    id="general-tab"
                                    data-bs-target="#general-tab-pane"
                                    role="tab"
                                    aria-controls="general-tab-pane"
                                    aria-selected="{{ $leadConfigTab === 'general-tab-pane' ? 'true' : 'false' }}"
                                    href="{{ route('admin.lead.configuration.index', ['tab' => 'general-tab-pane']) }}"
                                    @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                {{ __('General') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $leadConfigTab === 'customer-tab-pane' ? 'active' : '' }}"
                                    id="customer-tab"
                                    data-bs-target="#customer-tab-pane"
                                    role="tab"
                                    aria-controls="customer-tab-pane"
                                    aria-selected="{{ $leadConfigTab === 'customer-tab-pane' ? 'true' : 'false' }}"
                                    href="{{ route('admin.lead.configuration.index', ['tab' => 'customer-tab-pane']) }}"
                                    @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                {{ __('Customer Related') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $leadConfigTab === 'provider-tab-pane' ? 'active' : '' }}"
                                    id="provider-tab"
                                    data-bs-target="#provider-tab-pane"
                                    role="tab"
                                    aria-controls="provider-tab-pane"
                                    aria-selected="{{ $leadConfigTab === 'provider-tab-pane' ? 'true' : 'false' }}"
                                    href="{{ route('admin.lead.configuration.index', ['tab' => 'provider-tab-pane']) }}"
                                    @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                {{ __('Provider Related') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $leadConfigTab === 'future-customer-tab-pane' ? 'active' : '' }}"
                                    id="future-customer-tab"
                                    data-bs-target="#future-customer-tab-pane"
                                    role="tab"
                                    aria-controls="future-customer-tab-pane"
                                    aria-selected="{{ $leadConfigTab === 'future-customer-tab-pane' ? 'true' : 'false' }}"
                                    href="{{ route('admin.lead.configuration.index', ['tab' => 'future-customer-tab-pane']) }}"
                                    @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                {{ __('Future Customer Related') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $leadConfigTab === 'future-provider-tab-pane' ? 'active' : '' }}"
                                    id="future-provider-tab"
                                    data-bs-target="#future-provider-tab-pane"
                                    role="tab"
                                    aria-controls="future-provider-tab-pane"
                                    aria-selected="{{ $leadConfigTab === 'future-provider-tab-pane' ? 'true' : 'false' }}"
                                    href="{{ route('admin.lead.configuration.index', ['tab' => 'future-provider-tab-pane']) }}"
                                    @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                {{ __('Future Provider Related') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $leadConfigTab === 'invalid-tab-pane' ? 'active' : '' }}"
                                    id="invalid-tab"
                                    data-bs-target="#invalid-tab-pane"
                                    role="tab"
                                    aria-controls="invalid-tab-pane"
                                    aria-selected="{{ $leadConfigTab === 'invalid-tab-pane' ? 'true' : 'false' }}"
                                    href="{{ route('admin.lead.configuration.index', ['tab' => 'invalid-tab-pane']) }}"
                                    @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                {{ __('Invalid Related') }}
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="leadConfigTabsContent">
                        <div class="tab-pane fade {{ $leadConfigTab === 'general-tab-pane' ? 'show active' : '' }}"
                             id="general-tab-pane"
                             role="tabpanel"
                             aria-labelledby="general-tab"
                             tabindex="0"
                             data-lead-config-tab-label="{{ __('General') }}">
                            <div class="row g-2">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Lead_Source'),
                                        'type' => 'source',
                                        'items' => $sources,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Ad_Types'),
                                        'type' => 'ad_source',
                                        'items' => $adSources,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Districts'),
                                        'type' => 'district',
                                        'items' => $districts,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Outbound_Enquiry_Status'),
                                        'type' => 'outbound_enquiry_status',
                                        'items' => $outboundEnquiryStatuses ?? collect(),
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $leadConfigTab === 'customer-tab-pane' ? 'show active' : '' }}"
                             id="customer-tab-pane"
                             role="tabpanel"
                             aria-labelledby="customer-tab"
                             tabindex="0"
                             data-lead-config-tab-label="{{ __('Customer Related') }}">
                            <div class="row g-2">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Customer_lead_status'),
                                        'type' => 'customer_lead_status',
                                        'items' => $customerLeadStatuses,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Customer_lead_tags'),
                                        'type' => 'customer_lead_tag',
                                        'items' => $customerLeadTags,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._future-customer-reasons', [
                                        'configTitle' => translate('Customer_cancellation_reasons'),
                                        'configType' => 'cancellation_reason',
                                        'optionConfigType' => 'cancellation_reason_option',
                                        'items' => $cancellationReasons,
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $leadConfigTab === 'provider-tab-pane' ? 'show active' : '' }}"
                             id="provider-tab-pane"
                             role="tabpanel"
                             aria-labelledby="provider-tab"
                             tabindex="0"
                             data-lead-config-tab-label="{{ __('Provider Related') }}">
                            <div class="row g-2">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Provider_lead_status'),
                                        'type' => 'provider_lead_status',
                                        'items' => $providerLeadStatuses,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._future-customer-reasons', [
                                        'configTitle' => translate('Provider_cancellation_reasons'),
                                        'configType' => 'provider_cancellation_reason',
                                        'optionConfigType' => 'provider_cancellation_reason_option',
                                        'items' => $providerCancellationReasons,
                                    ])
                                </div>
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._card', [
                                        'title' => translate('Provider_Checklist_Items'),
                                        'type' => 'provider_checklist_item',
                                        'items' => $providerChecklistItems,
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $leadConfigTab === 'future-customer-tab-pane' ? 'show active' : '' }}"
                             id="future-customer-tab-pane"
                             role="tabpanel"
                             aria-labelledby="future-customer-tab"
                             tabindex="0"
                             data-lead-config-tab-label="{{ __('Future Customer Related') }}">
                            <div class="row g-2">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._future-customer-reasons', [
                                        'items' => $futureCustomerReasons,
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $leadConfigTab === 'future-provider-tab-pane' ? 'show active' : '' }}"
                             id="future-provider-tab-pane"
                             role="tabpanel"
                             aria-labelledby="future-provider-tab"
                             tabindex="0"
                             data-lead-config-tab-label="{{ __('Future Provider Related') }}">
                            <div class="row g-2">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._future-customer-reasons', [
                                        'configTitle' => translate('Future_provider_reasons'),
                                        'configType' => 'future_provider_reason',
                                        'optionConfigType' => 'future_provider_reason_option',
                                        'items' => $futureProviderReasons,
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $leadConfigTab === 'invalid-tab-pane' ? 'show active' : '' }}"
                             id="invalid-tab-pane"
                             role="tabpanel"
                             aria-labelledby="invalid-tab"
                             tabindex="0"
                             data-lead-config-tab-label="{{ __('Invalid Related') }}">
                            <div class="row g-2">
                                <div class="col-lg-6">
                                    @include('leadmanagement::admin.configuration.partials._future-customer-reasons', [
                                        'configTitle' => translate('Invalid_reasons'),
                                        'configType' => 'invalid_reason',
                                        'optionConfigType' => 'invalid_reason_option',
                                        'items' => $invalidReasons,
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('css_or_js')
    <style>
        .lead-config-page h3.c1 { font-size: 1rem; }
        .lead-config-page .table > :not(caption) > * > * { padding: 0.35rem 0.5rem; }
        .lead-config-page .lead-config-desc {
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
@endpush
@push('script')
    <script>
        (function () {
            const storageKey = 'lead_config_active_tab';
            const tabIds = ['general-tab-pane', 'customer-tab-pane', 'provider-tab-pane', 'future-customer-tab-pane', 'future-provider-tab-pane', 'invalid-tab-pane'];

            function tabIdFromTarget(target) {
                const id = String(target || '').replace(/^#/, '');
                return tabIds.indexOf(id) === -1 ? '' : id;
            }

            function activeTabId() {
                const active = document.querySelector('#leadConfigTabs .nav-link.active');
                return tabIdFromTarget(active ? active.getAttribute('data-bs-target') : '');
            }

            function rememberLeadConfigTab(target) {
                const id = tabIdFromTarget(target);
                if (!id) return;
                try {
                    sessionStorage.setItem(storageKey, id);
                } catch (e) {}
                const url = new URL(window.location.href);
                if (url.searchParams.get('tab') === id && !url.hash) return;
                url.searchParams.set('tab', id);
                url.hash = '';
                history.replaceState(window.history.state, '', url.pathname + url.search);
            }

            function showLeadConfigTab(id) {
                const trigger = document.querySelector('#leadConfigTabs [data-bs-target="#' + id + '"]');
                if (!trigger || trigger.classList.contains('active') || !window.bootstrap || !bootstrap.Tab) return;
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            }

            function debounce(fn, ms) {
                let t;
                return function () {
                    const args = arguments;
                    const ctx = this;
                    clearTimeout(t);
                    t = setTimeout(function () {
                        fn.apply(ctx, args);
                    }, ms);
                };
            }

            function leadConfigRowText(tr) {
                return (tr.innerText || '').replace(/\s+/g, ' ').trim().toLowerCase();
            }

            function applyLeadConfigSearch(needle) {
                const hints = document.getElementById('leadConfigSearchHints');
                const tabNavHits = document.getElementById('leadConfigTabNavHits');
                const panes = document.querySelectorAll('#leadConfigTabsContent .tab-pane[data-lead-config-tab-label]');
                const tabCounts = [];
                needle = String(needle || '').trim().toLowerCase();

                const labelHitTriggers = [];
                if (needle.length >= 2) {
                    document.querySelectorAll('#leadConfigTabs a[data-bs-target]').forEach(function (a) {
                        const text = (a.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                        if (text.indexOf(needle) !== -1) {
                            labelHitTriggers.push(a);
                        }
                    });
                }
                if (tabNavHits) {
                    tabNavHits.innerHTML = '';
                    if (labelHitTriggers.length) {
                        tabNavHits.classList.remove('d-none');
                        const openLabel = {{ json_encode(translate('Open')) }};
                        labelHitTriggers.forEach(function (a) {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'btn btn-sm btn-outline-primary';
                            const title = (a.textContent || '').replace(/\s+/g, ' ').trim();
                            btn.textContent = openLabel + ': ' + title;
                            btn.addEventListener('click', function () {
                                if (a.href) {
                                    window.location.href = a.href;
                                    return;
                                }
                                rememberLeadConfigTab(a.getAttribute('data-bs-target'));
                            });
                            tabNavHits.appendChild(btn);
                        });
                    } else {
                        tabNavHits.classList.add('d-none');
                    }
                }

                panes.forEach(function (pane) {
                    const label = pane.getAttribute('data-lead-config-tab-label') || '';
                    let paneMatches = 0;
                    pane.querySelectorAll('.card').forEach(function (card) {
                        const tbody = card.querySelector('table tbody');
                        if (!tbody) {
                            return;
                        }
                        let anyVisible = false;
                        tbody.querySelectorAll('tr').forEach(function (tr) {
                            if (!needle) {
                                tr.classList.remove('d-none');
                                anyVisible = true;
                                paneMatches++;
                                return;
                            }
                            const match = leadConfigRowText(tr).indexOf(needle) !== -1;
                            tr.classList.toggle('d-none', !match);
                            if (match) {
                                anyVisible = true;
                                paneMatches++;
                            }
                        });
                        card.classList.toggle('d-none', !anyVisible);
                    });
                    if (needle && paneMatches > 0) {
                        tabCounts.push({ label: label, count: paneMatches });
                    }
                });

                if (hints) {
                    if (!needle) {
                        hints.classList.add('d-none');
                        hints.innerHTML = '';
                        return;
                    }
                    if (!tabCounts.length && !labelHitTriggers.length) {
                        hints.textContent = {{ json_encode(translate('No results')) }};
                        hints.classList.remove('d-none');
                        return;
                    }
                    if (!tabCounts.length) {
                        hints.classList.add('d-none');
                        hints.innerHTML = '';
                    } else {
                        hints.classList.remove('d-none');
                        hints.innerHTML = tabCounts
                            .map(function (x) {
                                return x.label + ' (' + x.count + ')';
                            })
                            .join(' · ');
                    }
                }
            }

            function initLeadConfigPage() {
                const tabs = document.getElementById('leadConfigTabs');
                if (!tabs || tabs.dataset.tabMemoryBound === '1') return;
                tabs.dataset.tabMemoryBound = '1';

                const searchInput = document.getElementById('leadConfigSearchInput');
                const runSearch = debounce(function () {
                    applyLeadConfigSearch(searchInput ? searchInput.value : '');
                }, 200);

                tabs.addEventListener('click', function (event) {
                    const trigger = event.target.closest('[data-bs-target]');
                    if (!trigger) return;
                    rememberLeadConfigTab(trigger.getAttribute('data-bs-target'));
                });
                tabs.addEventListener('shown.bs.tab', function (event) {
                    rememberLeadConfigTab(event.target.getAttribute('data-bs-target'));
                });

                document.addEventListener('submit', function (event) {
                    const form = event.target;
                    if (!form || !form.closest || !form.closest('.lead-config-page')) return;
                    const id = activeTabId();
                    if (!id) return;
                    rememberLeadConfigTab(id);
                    let field = form.querySelector('input[name="return_tab"]');
                    if (!field) {
                        field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = 'return_tab';
                        form.appendChild(field);
                    }
                    field.value = id;
                }, true);

                const renderedTab = activeTabId();
                const urlTab = tabIdFromTarget(new URL(window.location.href).searchParams.get('tab'));
                let storedTab = '';
                try {
                    storedTab = tabIdFromTarget(sessionStorage.getItem(storageKey));
                } catch (e) {}
                // Keep the tab this response already rendered. A leftover
                // address or saved tab must not switch Future Customer
                // (or any other section) back to General.
                if (renderedTab && urlTab && renderedTab !== urlTab) {
                    rememberLeadConfigTab(renderedTab);
                } else {
                    const tabToOpen = renderedTab || urlTab || storedTab;
                    if (tabToOpen) {
                        rememberLeadConfigTab(tabToOpen);
                        if (tabToOpen !== renderedTab) {
                            showLeadConfigTab(tabToOpen);
                        }
                    }
                }

                if (searchInput) {
                    searchInput.addEventListener('input', runSearch);
                    searchInput.addEventListener('search', function () {
                        if (!searchInput.value) {
                            applyLeadConfigSearch('');
                        }
                    });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initLeadConfigPage);
            } else {
                initLeadConfigPage();
            }
            document.addEventListener('turbo:load', initLeadConfigPage);
            document.addEventListener('turbo:frame-load', initLeadConfigPage);
        })();
    </script>
@endpush

