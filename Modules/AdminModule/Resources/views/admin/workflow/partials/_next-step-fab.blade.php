@if(!empty($workflowContext['scenario']) && !empty($workflowContext['steps']))
    @php
        $wfEntityType = $workflowContext['entity_type'] ?? 'lead';
        $wfEntityId = $workflowContext['entity_id'] ?? 0;
        $wfCanEdit = $wfCanEdit ?? auth()->user()?->can('lead_update');
        if ($wfEntityType === 'booking') {
            $wfCanEdit = auth()->user()?->can('booking_can_manage_status') || auth()->user()?->can('booking_view');
        }
        $wfProgress = (int) ($workflowContext['progress_percent'] ?? 0);
        $wfNextLabel = $workflowContext['next']['label'] ?? translate('Next_Step_Guide');
    @endphp

    @once
        @push('css_or_js')
            <style>
                .workflow-fab {
                    position: fixed;
                    bottom: 1.5rem;
                    right: 1.5rem;
                    z-index: 1040;
                    display: flex;
                    flex-direction: column;
                    align-items: flex-end;
                    gap: .75rem;
                    pointer-events: none;
                }
                .workflow-fab > * { pointer-events: auto; }
                .workflow-fab-panel {
                    width: min(22rem, calc(100vw - 2rem));
                    max-height: min(70vh, 32rem);
                    display: flex;
                    flex-direction: column;
                    background: #fff;
                    border: 1px solid #e2e8f0;
                    border-radius: 14px;
                    box-shadow: 0 8px 32px rgba(15, 23, 42, .14);
                    opacity: 0;
                    visibility: hidden;
                    transform: translateY(12px) scale(.96);
                    transform-origin: bottom right;
                    transition: opacity .2s ease, transform .2s ease, visibility .2s;
                }
                .workflow-fab.is-open .workflow-fab-panel {
                    opacity: 1;
                    visibility: visible;
                    transform: translateY(0) scale(1);
                }
                .workflow-fab-panel__head {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: .5rem;
                    padding: .75rem 1rem;
                    border-bottom: 1px solid #e2e8f0;
                    flex-shrink: 0;
                }
                .workflow-fab-panel__title {
                    display: flex;
                    align-items: center;
                    gap: .35rem;
                    font-size: .875rem;
                    font-weight: 700;
                    color: #0f172a;
                    margin: 0;
                }
                .workflow-fab-panel__title .material-icons { font-size: 18px; color: #0f766e; }
                .workflow-fab-panel__close {
                    border: none;
                    background: transparent;
                    color: #64748b;
                    line-height: 1;
                    padding: .15rem;
                    border-radius: 6px;
                }
                .workflow-fab-panel__close:hover { background: #f1f5f9; color: #0f172a; }
                .workflow-fab-panel__body {
                    padding: .75rem 1rem 1rem;
                    overflow-y: auto;
                }
                .workflow-fab-trigger {
                    display: inline-flex;
                    align-items: center;
                    gap: .5rem;
                    border: none;
                    border-radius: 999px;
                    padding: .65rem 1rem .65rem .85rem;
                    background: linear-gradient(135deg, #0f766e, #0d9488);
                    color: #fff;
                    box-shadow: 0 6px 20px rgba(15, 118, 110, .35);
                    font-size: .8125rem;
                    font-weight: 700;
                    max-width: min(18rem, calc(100vw - 2rem));
                    transition: transform .15s ease, box-shadow .15s ease;
                }
                .workflow-fab-trigger:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 8px 24px rgba(15, 118, 110, .4);
                    color: #fff;
                }
                .workflow-fab-trigger .material-icons { font-size: 20px; }
                .workflow-fab-trigger__text {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-start;
                    min-width: 0;
                    text-align: left;
                }
                .workflow-fab-trigger__label { line-height: 1.2; white-space: nowrap; }
                .workflow-fab-trigger__hint {
                    font-size: .68rem;
                    font-weight: 500;
                    opacity: .9;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-width: 11rem;
                }
                .workflow-fab-trigger__badge {
                    flex-shrink: 0;
                    min-width: 2rem;
                    padding: .15rem .45rem;
                    border-radius: 999px;
                    background: rgba(255, 255, 255, .2);
                    font-size: .68rem;
                    font-weight: 800;
                }
                .workflow-fab.is-open .workflow-fab-trigger { box-shadow: 0 4px 14px rgba(15, 118, 110, .28); }
                @media (max-width: 575.98px) {
                    .workflow-fab { bottom: 1rem; right: 1rem; }
                    .workflow-fab-trigger__hint { display: none; }
                }
            </style>
        @endpush
    @endonce

    <div class="workflow-fab" id="workflow-fab-root">
        <div class="workflow-fab-panel"
             id="workflow-next-step-card"
             role="dialog"
             aria-labelledby="workflow-fab-title"
             aria-hidden="true"
             data-entity-type="{{ $wfEntityType }}"
             data-entity-id="{{ $wfEntityId }}">
            <div class="workflow-fab-panel__head">
                <h2 class="workflow-fab-panel__title" id="workflow-fab-title">
                    <span class="material-icons" aria-hidden="true">route</span>
                    {{ translate('Next_Step_Guide') }}
                </h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">{{ $wfProgress }}%</span>
                    <button type="button" class="workflow-fab-panel__close" id="workflow-fab-close" aria-label="{{ translate('Close') }}">
                        <span class="material-icons" style="font-size:20px;">close</span>
                    </button>
                </div>
            </div>
            <div class="workflow-fab-panel__body">
                <p class="text-muted small mb-2">{{ $workflowContext['scenario_label'] ?? '' }}</p>
                @if(!empty($workflowContext['next']))
                    <div class="alert alert-info py-2 px-2 mb-3 small">
                        <strong>{{ translate('Next') }}:</strong> {{ $workflowContext['next']['label'] }}
                        @if(!empty($workflowContext['next']['detail']))
                            <div class="mt-1 opacity-75">{{ $workflowContext['next']['detail'] }}</div>
                        @endif
                    </div>
                @endif
                <ul class="workflow-step-list list-unstyled mb-0">
                    @foreach($workflowContext['steps'] as $step)
                        @php
                            $isDone = !empty($step['done']);
                            $isCurrent = ($step['status'] ?? '') === 'current';
                            $icon = $isDone ? 'check_circle' : ($isCurrent ? 'radio_button_checked' : 'radio_button_unchecked');
                            $tone = $isDone ? 'text-success' : ($isCurrent ? 'text-primary' : 'text-muted');
                        @endphp
                        <li class="workflow-step-item d-flex align-items-start gap-2 mb-2 {{ $tone }}"
                            data-step-key="{{ $step['key'] }}"
                            data-is-done="{{ $isDone ? '1' : '0' }}"
                            data-manual="{{ !empty($step['manual']) ? '1' : '0' }}">
                            @if(!empty($step['manual']) && $wfCanEdit)
                                <button type="button"
                                        class="btn btn-sm p-0 border-0 bg-transparent workflow-step-toggle {{ $tone }}"
                                        title="{{ translate('Mark_step_done') }}"
                                        data-step-key="{{ $step['key'] }}">
                                    <span class="material-icons workflow-step-icon" style="font-size:20px;">{{ $isDone ? 'check_box' : 'check_box_outline_blank' }}</span>
                                </button>
                            @else
                                <span class="material-icons flex-shrink-0" style="font-size:20px;">{{ $icon }}</span>
                            @endif
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">{{ $step['label'] }}</div>
                                @if(!empty($step['detail']) && $isCurrent)
                                    <div class="text-muted" style="font-size:11px;">{{ $step['detail'] }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if(!empty($workflowContext['close_steps']))
                    <hr class="my-2">
                    <p class="small fw-semibold mb-2">{{ translate('Before_Completed') }}</p>
                    <ul class="workflow-step-list list-unstyled mb-0">
                        @foreach($workflowContext['close_steps'] as $step)
                            @php
                                $isDone = !empty($step['done']);
                                $isCurrent = ($step['status'] ?? '') === 'current';
                                $icon = $isDone ? 'check_circle' : ($isCurrent ? 'radio_button_checked' : 'radio_button_unchecked');
                                $tone = $isDone ? 'text-success' : ($isCurrent ? 'text-primary' : 'text-muted');
                            @endphp
                            <li class="workflow-step-item d-flex align-items-start gap-2 mb-2 {{ $tone }}"
                                data-step-key="{{ $step['key'] }}"
                                data-is-done="{{ $isDone ? '1' : '0' }}"
                                data-manual="{{ !empty($step['manual']) ? '1' : '0' }}">
                                @if(!empty($step['manual']) && $wfCanEdit)
                                    <button type="button"
                                            class="btn btn-sm p-0 border-0 bg-transparent workflow-step-toggle {{ $tone }}"
                                            data-step-key="{{ $step['key'] }}">
                                        <span class="material-icons workflow-step-icon" style="font-size:20px;">{{ $isDone ? 'check_box' : 'check_box_outline_blank' }}</span>
                                    </button>
                                @else
                                    <span class="material-icons flex-shrink-0" style="font-size:20px;">{{ $icon }}</span>
                                @endif
                                <div class="small">{{ $step['label'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <button type="button"
                class="workflow-fab-trigger"
                id="workflow-fab-trigger"
                aria-expanded="false"
                aria-controls="workflow-next-step-card">
            <span class="material-icons" aria-hidden="true">route</span>
            <span class="workflow-fab-trigger__text">
                <span class="workflow-fab-trigger__label">{{ translate('Next_Step') }}</span>
                <span class="workflow-fab-trigger__hint">{{ $wfNextLabel }}</span>
            </span>
            <span class="workflow-fab-trigger__badge">{{ $wfProgress }}%</span>
        </button>
    </div>
@endif
