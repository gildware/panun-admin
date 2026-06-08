@php
    use Modules\LeadManagement\Services\OutboundCallContextService;

    $contextKeys = $contextKeys ?? OutboundCallContextService::CONTEXT_KEYS;
    $callContext = is_array($callContext ?? null) ? $callContext : [];
    $phone = (string) ($phone ?? '');
    $needsRefresh = (bool) ($needsRefresh ?? false);
    $summaryActionTitle = (string) ($summaryActionTitle ?? translate('Generate_summary'));
    $contextIsFilled = function ($value): bool {
        $text = trim((string) $value);
        if ($text === '') {
            return false;
        }

        return !in_array(strtolower($text), ['—', '-', 'n/a', 'na', 'none', 'null'], true);
    };
    $contextCopyLines = [];
    $hasEmptyContext = false;
    foreach ($contextKeys as $varKey) {
        $varValue = $callContext[$varKey] ?? '';
        if ($varKey === 'lead_summary') {
            if ($contextIsFilled($varValue)) {
                $contextCopyLines[] = translate('Lead_Summary') . ': ' . $varValue;
            }
            continue;
        }
        if ($contextIsFilled($varValue)) {
            $label = $varKey === 'call_reason'
                ? translate('Call_Reason')
                : str_replace('_', ' ', ucfirst($varKey));
            $displayValue = $varKey === 'call_reason'
                ? (($callReasonLabels ?? [])[$varValue] ?? $varValue)
                : $varValue;
            $contextCopyLines[] = $label . ': ' . $displayValue;
        } else {
            $hasEmptyContext = true;
        }
    }
    $contextCopyText = implode("\n", $contextCopyLines);
@endphp
<div class="card voice-call-detail-box shadow-sm flex-grow-1 wa-followup-call-context-card">
    <div class="card-header voice-call-detail-box__header wa-followup-call-context-header">
        <div class="wa-followup-call-context-heading">
            <div class="wa-followup-call-context-heading__main">
                <span class="material-icons" aria-hidden="true">settings_phone</span>
                <span>{{ translate('WhatsApp_followup_call_context_title') }}</span>
            </div>
            <span class="wa-followup-call-context-heading__sub">{{ translate('WhatsApp_followup_call_context_subtitle') }}</span>
        </div>
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            @if($hasEmptyContext)
                <button type="button"
                        class="btn btn-sm btn-outline-secondary voice-call-extracted-view-all wa-followup-call-context-view-all">
                    {{ translate('view_all') }}
                </button>
            @endif
            <button type="button"
                    class="voice-call-copy-btn wa-followup-call-context-copy {{ $contextCopyText !== '' ? '' : 'd-none' }}"
                    title="{{ translate('Copy') }}"
                    data-copy-b64="{{ $contextCopyText !== '' ? base64_encode($contextCopyText) : '' }}">
                <span class="material-icons" aria-hidden="true">content_copy</span>
            </button>
        </div>
    </div>
    <div class="card-body wa-followup-call-context-body p-0">
        <div class="table-responsive">
            <table class="table table-sm wa-followup-context-table mb-0">
                <colgroup>
                    <col class="wa-followup-context-col-label">
                    <col class="wa-followup-context-col-value">
                </colgroup>
                <tbody class="wa-followup-call-context-grid">
                    @foreach($contextKeys as $varKey)
                        @php
                            $varValue = $callContext[$varKey] ?? '';
                            $isFilled = $contextIsFilled($varValue);
                        @endphp
                        @if($varKey === 'lead_summary')
                            <tr class="wa-followup-context-row wa-followup-lead-summary-row">
                                <th scope="row" class="wa-followup-context-label wa-followup-lead-summary-label">
                                    <span class="wa-followup-context-label__text">{{ translate('Lead_Summary') }}</span>
                                    <button type="button"
                                            class="voice-call-copy-btn wa-followup-generate-summary"
                                            data-phone="{{ $phone }}"
                                            title="{{ $summaryActionTitle }}"
                                            aria-label="{{ $summaryActionTitle }}">
                                        <span class="material-icons" aria-hidden="true">autorenew</span>
                                    </button>
                                </th>
                                <td class="wa-followup-context-value wa-followup-lead-summary-value {{ $isFilled ? '' : 'text-muted' }}">
                                    @if($needsRefresh)
                                        <p class="text-warning small mb-1 wa-followup-summary-outdated">{{ translate('Summary_outdated') }}</p>
                                    @endif
                                    {{ $isFilled ? $varValue : translate('No_summary_yet') }}
                                </td>
                            </tr>
                        @else
                            <tr class="wa-followup-context-row {{ $isFilled ? '' : 'wa-followup-context-row--empty' }}">
                                <th scope="row" class="wa-followup-context-label">
                                    @if($varKey === 'call_reason')
                                        {{ translate('Call_Reason') }}
                                    @else
                                        {{ str_replace('_', ' ', ucfirst($varKey)) }}
                                    @endif
                                </th>
                                <td class="wa-followup-context-value">
                                    @if($isFilled)
                                        @if($varKey === 'call_reason')
                                            {{ ($callReasonLabels ?? [])[$varValue] ?? $varValue }}
                                        @else
                                            {{ $varValue }}
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
