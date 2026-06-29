@once
    @push('css_or_js')
        <style>
            .notification-scenario-module-panel {
                display: none;
            }
            .notification-scenario-module-panel.is-active {
                display: block;
            }
            .notification-scenario-scroll-list {
                max-height: calc(100vh - 280px);
                min-height: 320px;
                overflow-y: auto;
                overflow-x: hidden;
                padding-right: 4px;
            }
            .notification-scenario-scroll-list::-webkit-scrollbar {
                width: 6px;
            }
            .notification-scenario-scroll-list::-webkit-scrollbar-thumb {
                background: #ced4da;
                border-radius: 6px;
            }
            .notification-scenario-audience-table th,
            .notification-scenario-audience-table td {
                font-size: 12px;
                vertical-align: middle;
            }
            .notification-scenario-audience-table th {
                background: #f8f9fa;
                font-weight: 600;
            }
            .notification-scenario-badge-actor {
                background: #e7f1ff;
                color: #0d6efd;
                border: 1px solid #cfe2ff;
            }
            .notification-scenario-badge-audience-customer {
                background: #e8f5e9;
                color: #2e7d32;
                border: 1px solid #c8e6c9;
            }
            .notification-scenario-badge-audience-provider {
                background: #fff3e0;
                color: #ef6c00;
                border: 1px solid #ffe0b2;
            }
            .notification-scenario-badge-audience-admin {
                background: #f3e5f5;
                color: #7b1fa2;
                border: 1px solid #e1bee7;
            }
            .notification-scenario-editor-panel {
                border-top: 1px dashed #dee2e6;
                background: #fafbfc;
            }
            .notification-scenario-edit-btn.active {
                background-color: var(--bs-primary);
                border-color: var(--bs-primary);
                color: #fff;
            }
            .notification-scenario-accordion + .notification-scenario-accordion {
                margin-top: 12px;
            }
            .notification-scenario-accordion .table-toggle-btn,
            .notification-scenario-accordion .notification-scenario-toggle-header {
                border-radius: 8px;
            }
            .notification-scenario-accordion .table-toggle-btn.active,
            .notification-scenario-accordion .notification-scenario-toggle-header.active {
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }
            .notification-scenario-accordion .notification-scenario-toggle-header.active .notification-scenario-toggle-chevron {
                transform: rotate(-180deg);
                background-color: var(--bs-primary) !important;
                color: var(--bs-white);
            }
            .notification-scenario-accordion .notification-scenario-toggle-chevron i {
                font-size: 1.25rem;
                line-height: 1.4375rem;
            }
            .notification-scenario-accordion .table-custom-wrap {
                border-radius: 0 0 8px 8px;
            }
            .notification-scenario-tabs-wrap {
                margin-bottom: 1.25rem;
            }
            .notification-scenario-tabs-wrap .nav-link {
                white-space: nowrap;
            }
        </style>
    @endpush
@endonce

@php
    $activeModuleTab = $activeModuleTab ?? array_key_first($groupedScenarios);
@endphp

<div class="notification-scenario-tabs-root">
    <input type="hidden" id="notificationScenarioActiveTab" value="{{ $activeModuleTab }}">
    <div class="notification-scenario-tabs-wrap">
        <ul class="nav nav--tabs nav--tabs__style2 nav--tabs__booking-tally flex-wrap gap-2 align-items-center notification-scenario-module-tabs" role="tablist">
            @foreach($groupedScenarios as $moduleKey => $moduleScenarios)
                <li class="nav-item">
                    <button type="button"
                            class="nav-link notification-scenario-module-tab {{ $moduleKey === $activeModuleTab ? 'active' : '' }}"
                            data-module-tab="{{ $moduleKey }}"
                            role="tab"
                            aria-selected="{{ $moduleKey === $activeModuleTab ? 'true' : 'false' }}">
                        {{ translate(NOTIFICATION_SCENARIO_MODULE_LABELS[$moduleKey] ?? str_replace('_', ' ', $moduleKey)) }}
                        <span class="count">{{ count($moduleScenarios) }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    @foreach($groupedScenarios as $moduleKey => $moduleScenarios)
        <div class="notification-scenario-module-panel {{ $moduleKey === $activeModuleTab ? 'is-active' : '' }}"
             data-module-panel="{{ $moduleKey }}"
             role="tabpanel">

            <div class="notification-scenario-scroll-list">
                @foreach($moduleScenarios as $scenario)
                    @php
                        $scenarioId = $scenario['id'];
                        $actorKey = $scenario['trigger_actor'] ?? 'system';
                        $actorLabel = translate(NOTIFICATION_SCENARIO_ACTOR_LABELS[$actorKey] ?? ucfirst($actorKey));
                        $allWired = collect($scenario['audiences'])->every(fn ($a) => (bool) ($a['wired'] ?? false));
                        $anyUnwired = collect($scenario['audiences'])->contains(fn ($a) => ! ($a['wired'] ?? false));
                    @endphp
                    <div class="notification-scenario-accordion" id="scenario-{{ $scenarioId }}">
                        <div class="d-flex align-items-center gap-3 p-3 cursor-pointer transition {{ $loop->first ? 'active' : '' }} bg-white border cus-shadow notification-scenario-toggle-header">
                            <span class="rounded-full bg-light w-28 h-28 fz-14 d-inline-flex align-items-center justify-content-center flex-shrink-0 notification-scenario-toggle-chevron">
                                <i class="material-symbols-outlined">keyboard_arrow_down</i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="badge notification-scenario-badge-actor rounded-pill px-2 py-1">
                                        {{ translate('Trigger') }}: {{ $actorLabel }}
                                    </span>
                                    @if($allWired)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">{{ translate('Wired_in_app') }}</span>
                                    @elseif($anyUnwired)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">{{ translate('Partially_wired') }}</span>
                                    @endif
                                </div>
                                <h6 class="mb-1 fw-semibold text-dark">{{ $scenario['title'] }}</h6>
                                <p class="fz-12 text-muted mb-0">{{ $scenario['trigger_action'] }}</p>
                            </div>
                        </div>

                        <div class="table-custom-wrap bg-white border border-top-0 cus-shadow p-3 notification-scenario-toggle-body" @if(!$loop->first) style="display: none;" @endif>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 notification-scenario-audience-table">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Audience') }}</th>
                                        <th>{{ translate('Channel') }}</th>
                                        <th>{{ translate('Message_template') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th class="text-end">{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($scenario['audiences'] as $audienceIndex => $audienceRow)
                                        @php
                                            $audienceKey = $audienceRow['audience'] ?? 'customer';
                                            $audienceLabel = translate(NOTIFICATION_SCENARIO_AUDIENCE_LABELS[$audienceKey] ?? ucfirst($audienceKey));
                                            $channelKey = $audienceRow['channel'] ?? 'push';
                                            $channelLabel = translate(NOTIFICATION_SCENARIO_CHANNEL_LABELS[$channelKey] ?? ucfirst($channelKey));
                                            $messageKey = $audienceRow['key'] ?? null;
                                            $settingsType = $audienceRow['settings_type'] ?? null;
                                            $isWired = (bool) ($audienceRow['wired'] ?? false);
                                            $audienceNote = $audienceRow['note'] ?? null;
                                            $editorId = 'scenario-editor-' . $scenarioId . '-' . $audienceIndex;
                                            $badgeClass = match ($audienceKey) {
                                                'provider' => 'notification-scenario-badge-audience-provider',
                                                'admin' => 'notification-scenario-badge-audience-admin',
                                                default => 'notification-scenario-badge-audience-customer',
                                            };
                                            $notificationDef = $messageKey ? notification_definition_for_key($messageKey) : null;
                                            $queryType = $settingsType === 'provider_notification' ? 'providers' : 'customers';
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="badge {{ $badgeClass }} rounded-pill px-2 py-1">{{ $audienceLabel }}</span>
                                            </td>
                                            <td>{{ $channelLabel }}</td>
                                            <td>
                                                @if($messageKey)
                                                    <code class="fz-11">{{ $messageKey }}</code>
                                                    <span class="text-muted d-block fz-11">{{ notification_scenario_audience_message_label($messageKey) }}</span>
                                                @else
                                                    <span class="text-muted fz-11">{{ $audienceNote ?? translate('Admin_inbox_only') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($isWired)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ translate('Wired_in_app') }}</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ translate('Not_wired') }}</span>
                                                @endif
                                                @if($audienceNote && $messageKey)
                                                    <div class="fz-11 text-muted mt-1">{{ $audienceNote }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($messageKey && $notificationDef && $settingsType)
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-primary notification-scenario-edit-btn"
                                                            data-toggle-target="#{{ $editorId }}"
                                                            data-show-label="{{ translate('Edit_message') }}"
                                                            data-hide-label="{{ translate('Hide_message') }}">
                                                        {{ translate('Edit_message') }}
                                                    </button>
                                                @else
                                                    <span class="text-muted fz-11">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($messageKey && $notificationDef && $settingsType)
                                            <tr class="notification-scenario-editor-row">
                                                <td colspan="5" class="p-0 border-0">
                                                    <div id="{{ $editorId }}" class="notification-scenario-editor-panel d-none p-3">
                                                        <form method="POST"
                                                              action="{{ route('admin.configuration.set-message-setting', ['type' => $queryType]) }}"
                                                              class="notification-message-form">
                                                            @csrf
                                                            @method('PUT')
                                                            @include('businesssettingsmodule::admin.partials.notification-message-fields', [
                                                                'notification' => $notificationDef,
                                                                'settingsType' => $settingsType,
                                                                'dataValues' => $dataValues,
                                                                'language' => $language,
                                                                'hideFormHeader' => true,
                                                                'scenarioContext' => [
                                                                    'scenario_title' => $scenario['title'],
                                                                    'audience' => $audienceLabel,
                                                                    'actor' => $actorLabel,
                                                                ],
                                                            ])
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@push('script')
    <script>
        (function () {
            if (window.__notificationScenarioTabsBound) {
                return;
            }
            window.__notificationScenarioTabsBound = true;

            function updateModuleTabUrl(moduleKey) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', moduleKey);
                window.history.replaceState({}, '', url);
            }

            function activateModuleTab(moduleKey, updateUrl) {
                document.querySelectorAll('.notification-scenario-module-tab').forEach(function (btn) {
                    var isActive = btn.getAttribute('data-module-tab') === moduleKey;
                    btn.classList.toggle('active', isActive);
                    btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                document.querySelectorAll('.notification-scenario-module-panel').forEach(function (panel) {
                    var isActive = panel.getAttribute('data-module-panel') === moduleKey;
                    panel.classList.toggle('is-active', isActive);
                    if (isActive) {
                        var list = panel.querySelector('.notification-scenario-scroll-list');
                        if (list) {
                            list.scrollTop = 0;
                        }
                    }
                });

                var activeTabInput = document.getElementById('notificationScenarioActiveTab');
                if (activeTabInput) {
                    activeTabInput.value = moduleKey;
                }

                if (updateUrl) {
                    updateModuleTabUrl(moduleKey);
                }
            }

            function initModuleTabFromPage() {
                var activeTabInput = document.getElementById('notificationScenarioActiveTab');
                var moduleKey = activeTabInput ? activeTabInput.value : null;
                if (!moduleKey) {
                    var urlTab = new URL(window.location.href).searchParams.get('tab');
                    moduleKey = urlTab;
                }
                if (moduleKey && document.querySelector('[data-module-tab="' + moduleKey + '"]')) {
                    activateModuleTab(moduleKey, false);
                }
            }

            function toggleScenarioAccordion(header) {
                var body = header.nextElementSibling;
                if (!body || !body.classList.contains('notification-scenario-toggle-body')) {
                    return;
                }

                var isOpen = window.getComputedStyle(body).display !== 'none';
                body.style.display = isOpen ? 'none' : 'block';
                header.classList.toggle('active', !isOpen);
            }

            document.addEventListener('click', function (e) {
                var moduleBtn = e.target.closest('.notification-scenario-module-tab');
                if (moduleBtn) {
                    e.preventDefault();
                    activateModuleTab(moduleBtn.getAttribute('data-module-tab'), true);
                    return;
                }

                var scenarioHeader = e.target.closest('.notification-scenario-toggle-header');
                if (scenarioHeader) {
                    e.preventDefault();
                    toggleScenarioAccordion(scenarioHeader);
                }
            });

            document.addEventListener('DOMContentLoaded', initModuleTabFromPage);
            document.addEventListener('admin:page-loaded', initModuleTabFromPage);
       })();
    </script>
@endpush
