@php
    use Modules\LeadManagement\Entities\Lead;

    $leadTypes = Lead::leadTypes();
    $listId = (string) ($listId ?? 'run');
    $showCheckboxes = (bool) ($showCheckboxes ?? false);
    $inputName = (string) ($inputName ?? 'phones[]');
@endphp

@if($candidates->isEmpty())
    <p class="text-muted mb-0">{{ translate('WhatsApp_followup_automation_no_candidates') }}</p>
@else
    <div class="voice-cron-candidates-compact-list">
        @foreach($candidates as $index => $candidate)
            @php
                $phone = (string) ($candidate['phone'] ?? '');
                if ($phone === '') {
                    continue;
                }
                $type = (string) ($candidate['lead_type'] ?? '');
                $typeLabel = $leadTypes[$type] ?? ($type !== '' ? ucfirst($type) : translate('Unknown'));
                $collapseId = 'voice-cron-context-' . $listId . '-' . $index;
            @endphp
            <div class="voice-cron-candidate-item border rounded mb-2 overflow-hidden">
                <div class="d-flex align-items-center gap-2 px-3 py-2 bg-light">
                    @if($showCheckboxes)
                        <input type="checkbox"
                               class="form-check-input mt-0 voice-cron-dispatch-check flex-shrink-0"
                               name="{{ $inputName }}"
                               value="{{ $phone }}"
                               id="voice-cron-check-{{ $listId }}-{{ $index }}"
                               checked>
                    @endif
                    <div class="flex-grow-1 min-w-0 {{ $showCheckboxes ? '' : 'py-1' }}">
                        @if($showCheckboxes)
                            <label class="mb-0 d-block" for="voice-cron-check-{{ $listId }}-{{ $index }}">
                                <span class="fw-semibold">{{ $candidate['display_name'] ?? '—' }}</span>
                                <span class="text-muted ms-2">{{ $phone }}</span>
                            </label>
                        @else
                            <div class="fw-semibold text-truncate">{{ $candidate['display_name'] ?? '—' }}</div>
                            <div class="small text-muted text-truncate">{{ $phone }}</div>
                        @endif
                        <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                            <span class="badge bg-light text-dark">{{ $typeLabel }}</span>
                            @if(!empty($candidate['lead_status_label']))
                                <span class="badge rounded-pill {{ $candidate['lead_status_badge'] ?? 'bg-secondary' }}">
                                    {{ translate($candidate['lead_status_label']) }}
                                </span>
                            @endif
                            @if(!empty($candidate['silent_duration_label']))
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ $candidate['silent_duration_label'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary flex-shrink-0"
                            data-bs-toggle="collapse"
                            data-bs-target="#{{ $collapseId }}"
                            aria-expanded="false">
                        {{ translate('Context') }}
                    </button>
                </div>
                <div class="collapse" id="{{ $collapseId }}">
                    <div class="p-2 border-top bg-white">
                        @include('leadmanagement::admin.voice-calls._voice_cron_candidate_panel', [
                            'candidate' => $candidate,
                            'callReasonLabels' => $callReasonLabels ?? [],
                            'contextKeys' => $contextKeys ?? [],
                            'showHeader' => false,
                            'compact' => true,
                        ])
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
