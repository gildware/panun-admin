<section class="activity-panel" id="lead-activity">
    @php
        $activityTab = request('activity');
        if (! in_array($activityTab, ['comment', 'followup', 'change', 'call'], true)) {
            $activityTab = 'comment';
        }
    @endphp
    <div class="activity-toolbar">
        <div class="filter-pills" role="tablist" aria-label="{{ translate('Activity') }}">
            <button type="button" class="filter-pill {{ $activityTab === 'comment' ? 'is-active' : '' }}" data-activity-filter="comment">
                {{ translate('Comments') }} <span class="count">{{ $activityCommentCount }}</span>
            </button>
            <button type="button" class="filter-pill {{ $activityTab === 'followup' ? 'is-active' : '' }}" data-activity-filter="followup">
                {{ translate('Follow_ups') }} <span class="count">{{ $lead->followups->count() + (!empty($hasScheduledFollowup) ? 1 : 0) }}</span>
            </button>
            <button type="button" class="filter-pill {{ $activityTab === 'change' ? 'is-active' : '' }}" data-activity-filter="change">
                {{ translate('Change_History') }} <span class="count">{{ $activityChangeCount }}</span>
            </button>
            <button type="button" class="filter-pill {{ $activityTab === 'call' ? 'is-active' : '' }}" data-activity-filter="call">
                {{ translate('Call_Logs') }} <span class="count">{{ $activityCallCount }}</span>
            </button>
        </div>
    </div>

    {{-- Timeline view (legacy, kept for recording toggles) --}}
    <div class="timeline-view is-hidden" id="lead-activity-timeline">
        <div class="timeline">
            @if(!empty($hasScheduledFollowup))
                <div class="timeline-item" data-activity-type="followup">
                    <div class="timeline-icon timeline-icon--pending"><span class="material-icons">pending_actions</span></div>
                    <div class="timeline-content">
                        <div class="timeline-head">
                            <span class="timeline-title">{{ !empty($pendingFollowupIsOverdue) ? translate('Missed_Follow_up') : (!empty($followupNeedsAttention) ? translate('Follow_up_due') : translate('Scheduled')) }}</span>
                            <span class="timeline-time">{{ translate('due') }} {{ $lead->next_followup_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="timeline-body">{{ translate('Please_take_action') }}</div>
                    </div>
                </div>
            @endif

            @foreach($sortedComments->reverse() as $comment)
                @php
                    $commentAuthor = $comment->createdBy;
                    $commentAuthorName = $commentAuthor ? (trim(($commentAuthor->first_name ?? '') . ' ' . ($commentAuthor->last_name ?? '')) ?: $commentAuthor->email) : '—';
                @endphp
                <div class="timeline-item" data-activity-type="comment">
                    <div class="timeline-icon timeline-icon--comment"><span class="material-icons">comment</span></div>
                    <div class="timeline-content">
                        <div class="timeline-head">
                            <span class="timeline-title">{{ $commentAuthorName }}</span>
                            <span class="timeline-time">{{ $comment->created_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="timeline-body">{!! $commentParser->format($comment->body ?? '') !!}</div>
                    </div>
                </div>
            @endforeach

            @foreach($lead->followups as $followup)
                @php
                    $delayMeta = $followupDelayMeta[$followup->id] ?? null;
                    $dueAt = $delayMeta['due_at'] ?? null;
                    $fuUser = $followup->createdBy;
                    $fuName = $fuUser ? (trim(($fuUser->first_name ?? '') . ' ' . ($fuUser->last_name ?? '')) ?: $fuUser->email) : '—';
                @endphp
                <div class="timeline-item" data-activity-type="followup">
                    <div class="timeline-icon timeline-icon--followup"><span class="material-icons">{{ $followup->isRescheduled() ? 'update' : 'phone_callback' }}</span></div>
                    <div class="timeline-content">
                        <div class="timeline-head">
                            <span class="timeline-title">{{ $followup->followupStatusLabel() }}</span>
                            <span class="timeline-time">{{ $followup->followup_at?->format('d M Y, h:i A') ?? $followup->created_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="timeline-body">
                            {{ translate('Taken_By') }}: {{ $fuName }}
                            @if($followup->next_followup_at)
                                · {{ translate('Next_Follow_up_Date') }}: {{ $followup->next_followup_at->format('d M Y, h:i A') }}
                            @endif
                        </div>
                        <div class="timeline-meta">
                            @if($delayMeta && $dueAt && ! $followup->isRescheduled())
                                @if($delayMeta['on_time'])
                                    <span class="chip chip--success">{{ translate('On_time') }}</span>
                                @else
                                    <span class="chip chip--danger">{{ translate('Delayed_by') }} {{ $delayMeta['delay_label'] }}</span>
                                @endif
                            @elseif($followup->isRescheduled())
                                <span class="chip chip--info">{{ translate('Rescheduled') }}</span>
                            @endif
                            @if($followup->contactChannelLabel())
                                <span class="chip chip--primary">{{ $followup->contactChannelLabel() }}</span>
                            @endif
                            @if($followup->hasRecording() && $followup->recording_url)
                                <button type="button" class="ld-btn ld-btn-outline py-0 px-2 voice-call-details-toggle" style="font-size:.6875rem;" aria-expanded="false">{{ translate('View') }}</button>
                            @endif
                        </div>
                        @if($followup->hasRecording() && $followup->recording_url)
                            <div class="voice-call-details-inline d-none mt-2">
                                @include('leadmanagement::admin.leads.partials._followup_recording_details_panel', ['followup' => $followup, 'lead' => $lead])
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if(isset($changeLogs))
                @foreach($changeLogs as $log)
                    @php
                        $editorName = '—';
                        if ($log->changedByUser) {
                            $cb = $log->changedByUser;
                            $cbName = trim(($cb->first_name ?? '') . ' ' . ($cb->last_name ?? ''));
                            $editorName = $cbName ?: $cb->email;
                        }
                        $loggedAt = $log->created_at?->format('d M Y, h:i A') ?? '—';
                        $changes = $log->changes ?? [];
                    @endphp
                    @forelse($changes as $fieldKey => $change)
                        <div class="timeline-item" data-activity-type="change">
                            <div class="timeline-icon timeline-icon--change"><span class="material-icons">swap_horiz</span></div>
                            <div class="timeline-content">
                                <div class="timeline-head">
                                    <span class="timeline-title">{{ translate($change['label'] ?? $fieldKey) }}</span>
                                    <span class="timeline-time">{{ $loggedAt }}</span>
                                </div>
                                <div class="timeline-body">
                                    {{ $editorName }}: <span class="text-muted">{{ $change['old'] ?? '—' }}</span> → <strong>{{ $change['new'] ?? '—' }}</strong>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="timeline-item" data-activity-type="change">
                            <div class="timeline-icon timeline-icon--change"><span class="material-icons">edit</span></div>
                            <div class="timeline-content">
                                <div class="timeline-head">
                                    <span class="timeline-title">{{ translate('Change_History') }}</span>
                                    <span class="timeline-time">{{ $loggedAt }}</span>
                                </div>
                                <div class="timeline-body">{{ $editorName }}</div>
                            </div>
                        </div>
                    @endforelse
                @endforeach
            @endif

            @if(empty($hasPendingFollowup) && $lead->followups->isEmpty() && $sortedComments->isEmpty() && (empty($changeLogs) || $changeLogs->isEmpty()))
                <p class="text-muted small px-3 py-2 mb-0">{{ translate('No_follow_ups_yet') }}</p>
            @endif
        </div>
    </div>

    {{-- Table view --}}
    <div class="table-view {{ in_array($activityTab, ['followup', 'change', 'call'], true) ? 'is-visible' : '' }}" id="lead-activity-table">
        <div class="activity-table-section activity-table-section--followups" @if(in_array($activityTab, ['change', 'call'], true)) style="display:none;" @endif>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 pt-3 pb-0">
            <p class="table-section-label mb-0">{{ translate('Follow_up_History') }}</p>
            @if(empty($hasPendingFollowup) && empty($hasScheduledFollowup))
                <button type="button"
                        class="ld-btn ld-btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#addFollowupModal"
                        data-followup-mode="add">
                    <span class="material-icons" aria-hidden="true">event</span>
                    {{ translate('Add_Follow_up') }}
                </button>
            @elseif(!empty($hasScheduledFollowup))
                <button type="button"
                        class="ld-btn ld-btn-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#addFollowupModal"
                        data-followup-mode="take">
                    <span class="material-icons" aria-hidden="true">event_available</span>
                    {{ translate('Take_Follow_up') }}
                </button>
            @endif
        </div>
        @if($lead->followups->isEmpty() && empty($hasScheduledFollowup))
            <p class="text-muted small px-3 py-2 mb-0">{{ translate('No_follow_ups_yet') }}</p>
        @else
            <div class="table-responsive lead-followup-history-wrap">
                <table class="data-table lead-followup-history-table lead-followup-history-table--compact mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Scheduled') }}</th>
                            <th>{{ translate('Taken_on') }}</th>
                            <th>{{ translate('Delay') }}</th>
                            <th>{{ translate('Next') }}</th>
                            <th>{{ translate('Urgency') }}</th>
                            <th>{{ translate('Taken_By') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Via') }}</th>
                            <th class="text-end">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @if(!empty($hasScheduledFollowup))
                        @php
                            $scheduledAt = $lead->next_followup_at;
                            $scheduledUrgency = $lead->followups->first()?->urgency ?: 'medium';
                            $scheduledIsOverdue = $pendingFollowupIsOverdue ?? false;
                            $scheduledIsDue = ! $scheduledIsOverdue && ($followupNeedsAttention ?? false);
                        @endphp
                        <tr class="lead-followup-row lead-followup-row--scheduled">
                            <td class="text-nowrap">{{ $scheduledAt?->format('j M, g:i A') ?? '—' }}</td>
                            <td class="text-nowrap">—</td>
                            <td class="text-nowrap">—</td>
                            <td class="text-nowrap">—</td>
                            <td><span class="chip chip--{{ $scheduledUrgency === 'high' ? 'danger' : ($scheduledUrgency === 'low' ? 'primary' : 'warning') }}">{{ translate(ucfirst($scheduledUrgency)) }}</span></td>
                            <td>—</td>
                            <td>
                                @if($scheduledIsOverdue)
                                    <span class="chip chip--danger">{{ translate('Missed_Follow_up') }}</span>
                                @elseif($scheduledIsDue)
                                    <span class="chip chip--warning">{{ translate('Follow_up_due') }}</span>
                                @else
                                    <span class="chip chip--info">{{ translate('Scheduled') }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">—</td>
                            <td class="text-end text-nowrap">
                                @can('lead_update')
                                    <button type="button"
                                            class="ld-btn ld-btn-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#addFollowupModal"
                                            data-followup-mode="take">
                                        {{ translate('Take') }}
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endif
                    @foreach($lead->followups as $followup)
                        @php
                            $delayMeta = $followupDelayMeta[$followup->id] ?? null;
                            $dueAt = $delayMeta['due_at'] ?? null;
                            $fuUrgency = $followup->urgency ?: 'medium';
                        @endphp
                        <tr class="lead-followup-row {{ $loop->even ? 'lead-followup-row--alt' : '' }}">
                            <td class="text-nowrap">{{ $dueAt?->format('j M, g:i A') ?? '—' }}</td>
                            <td class="text-nowrap">{{ $followup->followup_at?->format('j M, g:i A') ?? ($followup->isRescheduled() ? $followup->created_at?->format('j M, g:i A') : '—') }}</td>
                            <td class="text-nowrap">
                                @if($delayMeta && $dueAt && ! $followup->isRescheduled())
                                    @if($delayMeta['on_time'])
                                        <span class="chip chip--success">{{ translate('On_time') }}</span>
                                    @else
                                        <span class="chip chip--danger" title="{{ translate('Delayed_by') }} {{ $delayMeta['delay_label'] }}">{{ $delayMeta['delay_label'] }}</span>
                                    @endif
                                @elseif($followup->isRescheduled())
                                    <span class="chip chip--info">{{ translate('Rescheduled') }}</span>
                                @else — @endif
                            </td>
                            <td class="text-nowrap">{{ $followup->next_followup_at?->format('j M, g:i A') ?? '—' }}</td>
                            <td><span class="chip chip--{{ $fuUrgency === 'high' ? 'danger' : ($fuUrgency === 'low' ? 'primary' : 'warning') }}">{{ translate(ucfirst($fuUrgency)) }}</span></td>
                            <td class="lead-followup-by-cell">
                                @if($followup->createdBy)
                                    @php $fuUser = $followup->createdBy; $fuName = trim(($fuUser->first_name ?? '') . ' ' . ($fuUser->last_name ?? '')); @endphp
                                    <span title="{{ $fuName ?: $fuUser->email }}">{{ $fuName ?: $fuUser->email }}</span>
                                @else — @endif
                            </td>
                            <td><span class="chip chip--{{ $followup->isRescheduled() ? 'info' : 'success' }}">{{ $followup->followupStatusLabel() }}</span></td>
                            <td class="text-nowrap">{{ $followup->contactChannelLabel() ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                @can('lead_update')
                                    @php
                                        $followupDeleteLabel = trim(
                                            ($followup->due_followup_at?->format('d M Y, h:i A') ?? '')
                                            .' · '.$followup->followupStatusLabel()
                                        );
                                    @endphp
                                    <div class="d-inline-flex flex-wrap justify-content-end align-items-center gap-1">
                                        <button type="button"
                                                class="ld-btn ld-btn-icon js-edit-lead-followup-btn"
                                                title="{{ translate('Edit') }}"
                                                aria-label="{{ translate('Edit') }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editLeadFollowupModal"
                                                data-followup-id="{{ $followup->id }}"
                                                data-url="{{ route('admin.lead.followups.edit', [$lead->id, $followup->id]) }}"
                                                data-status="{{ $followup->followup_status }}"
                                                data-date="{{ ($followup->due_followup_at ?? $followup->followup_at)?->format('Y-m-d\TH:i') }}"
                                                data-followup-at="{{ $followup->followup_at?->format('Y-m-d\TH:i') }}"
                                                data-channel="{{ $followup->contact_channel }}"
                                                data-urgency="{{ $followup->urgency ?: 'medium' }}"
                                                data-remarks="{{ $followup->remarks }}"
                                                data-next-at="{{ $followup->next_followup_at?->format('Y-m-d\TH:i') }}">
                                            <span class="material-icons" aria-hidden="true">edit</span>
                                        </button>
                                        <button type="button"
                                                class="ld-btn ld-btn-icon ld-btn-icon--danger js-delete-lead-followup-btn"
                                                title="{{ translate('Delete') }}"
                                                aria-label="{{ translate('Delete') }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteLeadFollowupModal"
                                                data-url="{{ route('admin.lead.followups.destroy', [$lead->id, $followup->id]) }}"
                                                data-label="{{ $followupDeleteLabel }}">
                                            <span class="material-icons" aria-hidden="true">delete_outline</span>
                                        </button>
                                    </div>
                                @else
                                    —
                                @endcan
                            </td>
                        </tr>
                        <tr class="lead-followup-remarks-row {{ $loop->even ? 'lead-followup-remarks-row--alt' : '' }}">
                            <td colspan="9" class="lead-followup-remarks-cell">
                                <div class="lead-followup-remarks-block">
                                    <span class="lead-followup-remarks-label">{{ translate('Remarks') }}:</span>
                                    <span class="lead-followup-remarks-text">{{ $followup->remarks ?: '—' }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        </div>

        <div class="activity-table-section activity-table-section--changes" @if(in_array($activityTab, ['followup', 'call'], true)) style="display:none;" @endif>
        <p class="table-section-label mb-0 border-top">{{ translate('Change_History') }}</p>
        @if(isset($changeLogs) && $changeLogs->isNotEmpty())
            <div class="table-responsive">
                <table class="data-table lead-change-history-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Field') }}</th>
                            <th>{{ translate('Changed_from') }}</th>
                            <th>{{ translate('Changed_to') }}</th>
                            <th>{{ translate('Date_Time') }}</th>
                            <th>{{ translate('Edited_by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @php $changeRowIndex = 0; @endphp
                    @foreach($changeLogs as $log)
                        @php
                            $editorName = '—';
                            if ($log->changedByUser) {
                                $cb = $log->changedByUser;
                                $cbName = trim(($cb->first_name ?? '') . ' ' . ($cb->last_name ?? ''));
                                $editorName = $cbName ?: $cb->email;
                            }
                            $loggedAt = $log->created_at?->format('d M Y, h:i A') ?? '—';
                            $changes = $log->changes ?? [];
                        @endphp
                        @forelse($changes as $fieldKey => $change)
                            <tr class="lead-change-history-row {{ $changeRowIndex % 2 === 1 ? 'lead-change-history-row--alt' : '' }}">
                                <td>{{ translate($change['label'] ?? $fieldKey) }}</td>
                                <td class="text-muted">{{ $change['old'] ?? '—' }}</td>
                                <td>{{ $change['new'] ?? '—' }}</td>
                                <td class="text-nowrap">{{ $loggedAt }}</td>
                                <td>{{ $editorName }}</td>
                            </tr>
                            @php $changeRowIndex++; @endphp
                        @empty
                            <tr class="lead-change-history-row {{ $changeRowIndex % 2 === 1 ? 'lead-change-history-row--alt' : '' }}">
                                <td colspan="3" class="text-muted">—</td>
                                <td class="text-nowrap">{{ $loggedAt }}</td>
                                <td>{{ $editorName }}</td>
                            </tr>
                            @php $changeRowIndex++; @endphp
                        @endforelse
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted small px-3 py-2 mb-0">{{ translate('No_changes_recorded_yet') }}</p>
        @endif
        </div>

        <div class="activity-table-section activity-table-section--calls" @if($activityTab !== 'call') style="display:none;" @endif>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 pt-3 pb-0 border-top">
            <p class="table-section-label mb-0">{{ translate('Call_Logs') }}</p>
            <button type="button"
                    class="ld-btn ld-btn-primary js-add-call-log-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#addCallLogModal">
                <span class="material-icons" aria-hidden="true">add_call</span>
                {{ translate('Add_Call_Log') }}
            </button>
        </div>
        @if(($callLogs ?? collect())->isEmpty())
            <p class="text-muted small px-3 py-2 mb-0">{{ translate('No_call_logs_yet') }}</p>
        @else
            <div class="table-responsive">
                <table class="data-table lead-call-log-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Who_You_Called') }}</th>
                            <th>{{ translate('When_You_Called') }}</th>
                            <th>{{ translate('Remarks') }}</th>
                            <th>{{ translate('Recording') }}</th>
                            @can('lead_update')
                                <th class="text-end">{{ translate('Actions') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($callLogs as $callLog)
                        @php
                            $calledAt = $callLog['called_at'] ?? null;
                            $callLogFollowup = ($callLog['type'] ?? '') === 'followup' ? ($callLog['followup'] ?? null) : null;
                            $hasRecording = ($callLog['type'] ?? '') === 'initial'
                                ? ($lead->hasInitialCallRecording() && $lead->initial_call_recording_url)
                                : ($callLogFollowup?->hasRecording() && $callLogFollowup?->recording_url);
                            $canManageCallLog = $callLogFollowup && auth()->user()?->can('lead_update');
                        @endphp
                        <tr class="lead-call-log-row {{ $loop->even ? 'lead-call-log-row--alt' : '' }}">
                            <td>
                                @php
                                    $partyType = $callLog['called_party_type'] ?? \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_CUSTOMER;
                                    $partyLabel = match ($partyType) {
                                        \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_PROVIDER => translate('Provider'),
                                        \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_OTHER => translate('Other'),
                                        default => translate('Customer'),
                                    };
                                @endphp
                                <div class="d-flex flex-column gap-1">
                                    <span class="chip chip--primary align-self-start">{{ $partyLabel }}</span>
                                    <span>{{ $callLog['called_name'] ?: '—' }}</span>
                                    @if(!empty($callLog['called_number']))
                                        <span class="text-muted small">{{ $callLog['called_number'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $calledAt?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td>{{ $callLog['remarks'] ?: '—' }}</td>
                            <td class="text-nowrap">
                                @if($hasRecording)
                                    <button type="button" class="ld-btn ld-btn-outline voice-call-details-toggle" aria-expanded="false">{{ translate('View') }}</button>
                                @else — @endif
                            </td>
                            @can('lead_update')
                                <td class="text-end text-nowrap">
                                    @if($canManageCallLog)
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <button type="button"
                                                    class="btn btn-sm btn-link p-0 js-edit-call-log-btn"
                                                    title="{{ translate('Edit') }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addCallLogModal"
                                                    data-followup-id="{{ $callLogFollowup->id }}"
                                                    data-party-type="{{ $callLogFollowup->called_party_type ?: \Modules\LeadManagement\Entities\LeadFollowup::CALLED_PARTY_CUSTOMER }}"
                                                    data-provider-id="{{ $callLogFollowup->called_provider_id }}"
                                                    data-called-name="{{ $callLogFollowup->called_name }}"
                                                    data-called-number="{{ $callLogFollowup->called_number }}"
                                                    data-called-at="{{ ($callLogFollowup->followup_at ?? $callLogFollowup->created_at)?->format('Y-m-d\TH:i') }}"
                                                    data-remarks="{{ $callLogFollowup->remarks }}"
                                                    data-has-recording="{{ $callLogFollowup->hasRecording() ? '1' : '0' }}"
                                                    data-recording-name="{{ $callLogFollowup->recording_original_name }}">
                                                <span class="material-icons" aria-hidden="true">edit</span>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-link text-danger p-0 js-delete-call-log-btn"
                                                    title="{{ translate('Delete') }}"
                                                    data-url="{{ route('admin.lead.call-logs.destroy', [$lead->id, $callLogFollowup->id]) }}">
                                                <span class="material-icons" aria-hidden="true">delete_outline</span>
                                            </button>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endcan
                        </tr>
                        @if($hasRecording)
                            <tr class="voice-call-details-row d-none">
                                <td colspan="{{ auth()->user()?->can('lead_update') ? 5 : 4 }}" class="p-0 border-0">
                                    @if(($callLog['type'] ?? '') === 'initial')
                                        @include('leadmanagement::admin.leads.partials._initial_call_recording_details_panel', ['lead' => $lead])
                                    @else
                                        @include('leadmanagement::admin.leads.partials._followup_recording_details_panel', ['followup' => $callLog['followup'], 'lead' => $lead])
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        </div>
    </div>

    {{-- Comments panel (shown via filter) --}}
    <div class="comments-view {{ $activityTab === 'comment' ? 'is-visible' : '' }}" id="lead-activity-comments">
        <p class="text-muted small mb-2">{{ translate('Internal_comments_help') }}</p>
        <div class="lead-comments-list-wrap" id="leadCommentsListWrap">
            <div class="lead-comments-list" id="leadCommentsList">
                @forelse($sortedComments as $comment)
                    @include('leadmanagement::admin.leads.partials._comment-item', ['comment' => $comment, 'commentParser' => $commentParser])
                @empty
                    <div class="lead-comments-empty text-muted small" id="leadCommentsEmpty">{{ translate('No_comments_yet') }}</div>
                @endforelse
            </div>
        </div>

        @can('lead_update')
            <form method="POST"
                  action="{{ route('admin.lead.comments.store', $lead->id) }}"
                  id="leadCommentForm"
                  class="lead-comment-compose staff-chat-compose-wrap position-relative mt-2"
                  enctype="multipart/form-data">
                @csrf
                @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                @include('leadmanagement::admin.leads.partials._comment-compose')
                <textarea name="body"
                          id="leadCommentBody"
                          class="form-control form-control-sm staff-chat-message-input lead-comment-compose__input w-100"
                          rows="2"
                          maxlength="5000"
                          placeholder="{{ translate('Write_a_comment') }}"></textarea>
                @include('leadmanagement::admin.leads.partials._comment-attachments-compose')
                <div class="lead-comment-compose__footer d-flex flex-wrap align-items-center gap-2 pt-2">
                    <div class="lead-comment-compose__tags d-flex flex-wrap align-items-center gap-1">
                        <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-staff" data-tag-type="staff">
                            {{ translate('Staff') }}
                        </button>
                        <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-provider" data-tag-type="provider">
                            {{ translate('Provider') }}
                        </button>
                        <button type="button" class="btn btn-sm staff-tag-trigger staff-tag-btn staff-tag-btn-service" data-tag-type="service">
                            {{ translate('Service') }}
                        </button>
                    </div>
                    <span class="small text-muted lead-comment-compose__hint">{{ translate('Lead_comment_mention_hint') }}</span>
                    <button type="submit" class="ld-btn ld-btn-primary ms-auto flex-shrink-0">{{ translate('Add_Comment') }}</button>
                </div>
            </form>
        @endcan
    </div>
</section>

<section class="panel delete-panel">
    <div class="panel__body py-3">
        <p class="text-muted small mb-2">{{ translate('This_action_will_permanently_remove_the_lead_and_its_related_data.') }}</p>
        <button type="button" class="ld-btn ld-btn-outline text-danger border-danger" data-bs-toggle="modal" data-bs-target="#deleteLeadModal">{{ translate('Delete_Lead') }}</button>
    </div>
</section>
