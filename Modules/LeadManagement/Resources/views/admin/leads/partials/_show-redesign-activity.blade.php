<section class="activity-panel" id="lead-activity">
    @php
        $activityTab = request('activity');
        if (! in_array($activityTab, ['comment', 'followup', 'change'], true)) {
            $activityTab = 'comment';
        }
    @endphp
    <div class="activity-toolbar">
        <div class="filter-pills" role="tablist">
            <button type="button" class="filter-pill {{ $activityTab === 'comment' ? 'is-active' : '' }}" data-activity-filter="comment">
                {{ translate('Comments') }} <span class="count">{{ $activityCommentCount }}</span>
            </button>
            <button type="button" class="filter-pill {{ $activityTab === 'followup' ? 'is-active' : '' }}" data-activity-filter="followup">
                {{ translate('Follow_ups') }} <span class="count">{{ $lead->followups->count() + (!empty($hasPendingFollowup) ? 1 : 0) }}</span>
            </button>
            <button type="button" class="filter-pill {{ $activityTab === 'change' ? 'is-active' : '' }}" data-activity-filter="change">
                {{ translate('Change_History') }} <span class="count">{{ $activityChangeCount }}</span>
            </button>
        </div>
    </div>

    {{-- Timeline view (legacy, kept for recording toggles) --}}
    <div class="timeline-view is-hidden" id="lead-activity-timeline">
        <div class="timeline">
            @if(!empty($hasPendingFollowup))
                <div class="timeline-item" data-activity-type="followup">
                    <div class="timeline-icon timeline-icon--pending"><span class="material-icons">pending_actions</span></div>
                    <div class="timeline-content">
                        <div class="timeline-head">
                            <span class="timeline-title">{{ !empty($pendingFollowupIsOverdue) ? translate('Missed_Follow_up') : translate('Pending_Follow_up') }}</span>
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
    <div class="table-view {{ in_array($activityTab, ['followup', 'change'], true) ? 'is-visible' : '' }}" id="lead-activity-table">
        <div class="activity-table-section activity-table-section--followups" @if($activityTab === 'change') style="display:none;" @endif>
        <p class="table-section-label mb-0">{{ translate('Follow_up_History') }}</p>
        @if($lead->followups->isEmpty() && empty($hasPendingFollowup))
            <p class="text-muted small px-3 py-2 mb-0">{{ translate('No_follow_ups_yet') }}</p>
        @else
            <div class="table-responsive">
                <table class="data-table lead-followup-history-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Scheduled_for') }}</th>
                            <th>{{ translate('Taken_on') }}</th>
                            <th>{{ translate('Delay') }}</th>
                            <th>{{ translate('Next_Follow_up_Date') }}</th>
                            <th>{{ translate('Urgency') }}</th>
                            <th>{{ translate('Taken_By') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Follow_up_Taken_on') }}</th>
                            <th>{{ translate('Recording') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lead->followups as $followup)
                        @php
                            $delayMeta = $followupDelayMeta[$followup->id] ?? null;
                            $dueAt = $delayMeta['due_at'] ?? null;
                            $fuUrgency = $followup->urgency ?: 'medium';
                        @endphp
                        <tr class="lead-followup-row {{ $loop->even ? 'lead-followup-row--alt' : '' }}">
                            <td class="text-nowrap">{{ $dueAt?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td class="text-nowrap">{{ $followup->followup_at?->format('d M Y, h:i A') ?? ($followup->isRescheduled() ? $followup->created_at?->format('d M Y, h:i A') : '—') }}</td>
                            <td class="text-nowrap">
                                @if($delayMeta && $dueAt && ! $followup->isRescheduled())
                                    @if($delayMeta['on_time'])
                                        <span class="chip chip--success">{{ translate('On_time') }}</span>
                                    @else
                                        <span class="chip chip--danger">{{ translate('Delayed_by') }} {{ $delayMeta['delay_label'] }}</span>
                                    @endif
                                @elseif($followup->isRescheduled())
                                    <span class="chip chip--info">{{ translate('Rescheduled') }}</span>
                                @else — @endif
                            </td>
                            <td class="text-nowrap">{{ $followup->next_followup_at?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td><span class="chip chip--{{ $fuUrgency === 'high' ? 'danger' : ($fuUrgency === 'low' ? 'primary' : 'warning') }}">{{ translate(ucfirst($fuUrgency)) }}</span></td>
                            <td>
                                @if($followup->createdBy)
                                    @php $fuUser = $followup->createdBy; $fuName = trim(($fuUser->first_name ?? '') . ' ' . ($fuUser->last_name ?? '')); @endphp
                                    {{ $fuName ?: $fuUser->email }}
                                @else — @endif
                            </td>
                            <td><span class="chip chip--{{ $followup->isRescheduled() ? 'info' : 'success' }}">{{ $followup->followupStatusLabel() }}</span></td>
                            <td class="text-nowrap">{{ $followup->contactChannelLabel() ?? '—' }}</td>
                            <td class="text-nowrap">
                                @if($followup->hasRecording() && $followup->recording_url)
                                    <button type="button" class="ld-btn ld-btn-outline voice-call-details-toggle" aria-expanded="false">{{ translate('View') }}</button>
                                @else — @endif
                            </td>
                        </tr>
                        @if($followup->hasRecording() && $followup->recording_url)
                            <tr class="voice-call-details-row d-none">
                                <td colspan="9" class="p-0 border-0">
                                    @include('leadmanagement::admin.leads.partials._followup_recording_details_panel', ['followup' => $followup, 'lead' => $lead])
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        </div>

        <div class="activity-table-section activity-table-section--changes" @if($activityTab === 'followup') style="display:none;" @endif>
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
                  class="lead-comment-compose staff-chat-compose-wrap position-relative mt-2">
                @csrf
                @if(!empty($inModal))<input type="hidden" name="in_modal" value="1">@endif
                @include('leadmanagement::admin.leads.partials._comment-compose')
                <textarea name="body"
                          id="leadCommentBody"
                          class="form-control form-control-sm staff-chat-message-input lead-comment-compose__input w-100"
                          rows="2"
                          required
                          maxlength="5000"
                          placeholder="{{ translate('Write_a_comment') }}"></textarea>
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
