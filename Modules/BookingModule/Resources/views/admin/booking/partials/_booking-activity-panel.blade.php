@php
    $sortedComments = $sortedComments ?? collect();
    $activityFollowups = $activityFollowups ?? collect();
    $changeLogs = $booking->change_logs ?? collect();
    $customerName = $customerName ?? booking_display_customer_name($booking, $booking->service_address ?? null);
    $customerPhone = $customerPhone ?? booking_display_customer_phone($booking, $booking->service_address ?? null);
    $commentCount = $sortedComments->count();
    $followupCount = $activityFollowups->count();
    $changeCount = $changeLogs->count();

    $activityTab = request('activity');
    if (! in_array($activityTab, ['comment', 'followup', 'change', 'call'], true)) {
        $activityTab = 'comment';
    }

    $callFollowups = $activityFollowups->filter(
        fn ($followup) => $followup->contact_channel === \Modules\BookingModule\Entities\BookingFollowup::CHANNEL_CALL
    );
    $callLogs = collect();
    foreach ($callFollowups as $callFollowup) {
        $callLogs->push([
            'type' => 'followup',
            'called_party_type' => $callFollowup->called_party_type ?: \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_CUSTOMER,
            'called_number' => $callFollowup->called_number ?: $customerPhone,
            'called_name' => $callFollowup->called_name ?: $customerName,
            'called_at' => $callFollowup->followup_at ?? $callFollowup->date ?? $callFollowup->created_at,
            'remarks' => $callFollowup->remarks,
            'followup' => $callFollowup,
        ]);
    }
    $callLogs = $callLogs->sortByDesc(fn ($log) => $log['called_at']?->timestamp ?? 0)->values();
    $callCount = $callLogs->count();
@endphp
<section class="activity-panel" id="booking-activity">
    <div class="activity-toolbar">
        <div class="filter-pills" role="tablist">
            <button class="filter-pill {{ $activityTab === 'comment' ? 'is-active' : '' }}" type="button" data-activity-filter="comment">
                {{ translate('Comments') }} <span class="count">{{ $commentCount }}</span>
            </button>
            <button class="filter-pill {{ $activityTab === 'followup' ? 'is-active' : '' }}" type="button" data-activity-filter="followup">
                {{ translate('Followups') }} <span class="count">{{ $followupCount }}</span>
            </button>
            <button class="filter-pill {{ $activityTab === 'change' ? 'is-active' : '' }}" type="button" data-activity-filter="change">
                {{ translate('Change_History') }} <span class="count">{{ $changeCount }}</span>
            </button>
            <button class="filter-pill {{ $activityTab === 'call' ? 'is-active' : '' }}" type="button" data-activity-filter="call">
                {{ translate('Call_Logs') }} <span class="count">{{ $callCount }}</span>
            </button>
        </div>
        <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'followups']) }}" class="btn btn-demo-outline btn-sm">
            <span class="material-icons">event</span>{{ translate('Add_Follow_up') }}
        </a>
    </div>

    <div class="activity-view {{ $activityTab === 'comment' ? 'is-visible' : '' }}" data-activity-view="comment">
        <p class="activity-view__hint">{{ translate('Internal_booking_comments_help') }}</p>
        <div class="comments-wrap">
            @forelse($sortedComments as $comment)
                @include('bookingmodule::admin.booking.partials._comment-item', [
                    'comment' => $comment,
                    'commentParser' => $commentParser,
                    'demoCompact' => true,
                ])
            @empty
                <p class="text-muted small mb-0">{{ translate('No_comments_yet') }}</p>
            @endforelse
        </div>
        @can('booking_view')
            <form method="POST"
                  action="{{ route('admin.booking.comments.store', $booking->id) }}"
                  id="bookingCommentForm"
                  class="comment-compose lead-comment-compose staff-chat-compose-wrap position-relative mt-3"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="redirect_web_page" value="details">
                @include('leadmanagement::admin.leads.partials._comment-compose')
                <textarea name="body"
                          id="bookingCommentBody"
                          class="form-control form-control-sm staff-chat-message-input lead-comment-compose__input w-100"
                          rows="2"
                          maxlength="5000"
                          placeholder="{{ translate('Write_a_comment') }}"></textarea>
                @include('leadmanagement::admin.leads.partials._comment-attachments-compose')
                <div class="comment-compose__footer">
                    <button type="button" class="tag-btn staff-tag-trigger" data-tag-type="staff">{{ translate('Staff') }}</button>
                    <button type="button" class="tag-btn staff-tag-trigger" data-tag-type="provider">{{ translate('Provider') }}</button>
                    <button type="button" class="tag-btn staff-tag-trigger" data-tag-type="service">{{ translate('Service') }}</button>
                    <span class="comment-compose__hint">{{ translate('Type_@_for_staff_mentions') }}</span>
                    <button type="submit" class="btn btn-demo-accent btn-sm">{{ translate('Add_Comment') }}</button>
                </div>
            </form>
        @endcan
    </div>

    <div class="activity-view {{ $activityTab === 'followup' ? 'is-visible' : '' }}" data-activity-view="followup">
        <div class="activity-table-section activity-table-section--followups">
            @include('bookingmodule::admin.booking.partials._booking-followup-history-table', [
                'booking' => $booking,
                'followups' => $activityFollowups,
                'followupDelayMeta' => $followupDelayMeta ?? [],
                'showActionColumn' => true,
                'showSectionLabel' => false,
                'variant' => 'activity',
            ])
        </div>
    </div>

    <div class="activity-view {{ $activityTab === 'change' ? 'is-visible' : '' }}" data-activity-view="change">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ translate('Field') }}</th>
                        <th>{{ translate('from') }}</th>
                        <th>{{ translate('to') }}</th>
                        <th>{{ translate('When') }}</th>
                        <th>{{ translate('By') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($changeLogs->take(20) as $log)
                        @php
                            $actor = $log->changedBy
                                ? trim(($log->changedBy->first_name ?? '') . ' ' . ($log->changedBy->last_name ?? ''))
                                : ($log->actor_name ?: translate('System'));
                            $propertyKey = (string) ($log->property_key ?? '');
                            $displayTitle = $log->property_label ?: str_replace('_', ' ', $propertyKey);
                        @endphp
                        <tr>
                            <td>{{ $displayTitle }}</td>
                            <td class="text-muted">{{ Str::limit($log->old_value ?? '—', 40) }}</td>
                            <td>{{ Str::limit($log->new_value ?? '—', 40) }}</td>
                            <td>{{ $log->created_at?->format('d-M-Y h:ia') }}</td>
                            <td>{{ $actor }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">{{ translate('No_history_entries_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($changeCount > 20)
            <div class="text-end mt-2">
                <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'history']) }}" class="fz-12">{{ translate('View_all') }} →</a>
            </div>
        @endif
    </div>

    <div class="activity-view {{ $activityTab === 'call' ? 'is-visible' : '' }}" data-activity-view="call">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <p class="activity-view__hint mb-0">{{ translate('Call_Logs') }}</p>
            @can('booking_view')
                <button type="button"
                        class="btn btn-demo-accent btn-sm js-add-call-log-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#addCallLogModal">
                    <span class="material-icons" aria-hidden="true" style="font-size:16px;">add_call</span>
                    {{ translate('Add_Call_Log') }}
                </button>
            @endcan
        </div>
        @if($callLogs->isEmpty())
            <p class="text-muted small mb-0">{{ translate('No_call_logs_yet') }}</p>
        @else
            <div class="table-responsive">
                <table class="data-table booking-call-log-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Who_You_Called') }}</th>
                            <th>{{ translate('When_You_Called') }}</th>
                            <th>{{ translate('Remarks') }}</th>
                            <th>{{ translate('Recording') }}</th>
                            @can('booking_view')
                                <th class="text-end">{{ translate('Actions') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($callLogs as $callLog)
                        @php
                            $calledAt = $callLog['called_at'] ?? null;
                            $callLogFollowup = ($callLog['type'] ?? '') === 'followup' ? ($callLog['followup'] ?? null) : null;
                            $hasRecording = $callLogFollowup?->hasRecording() && $callLogFollowup?->recording_url;
                            $canManageCallLog = $callLogFollowup && auth()->user()?->can('booking_view');
                        @endphp
                        <tr class="booking-call-log-row {{ $loop->even ? 'booking-call-log-row--alt' : '' }}">
                            <td>
                                @php
                                    $partyType = $callLog['called_party_type'] ?? \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_CUSTOMER;
                                    $partyLabel = match ($partyType) {
                                        \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_PROVIDER => translate('Provider'),
                                        \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_OTHER => translate('Other'),
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
                                    <button type="button" class="btn btn-demo-outline btn-sm voice-call-details-toggle" aria-expanded="false">{{ translate('View') }}</button>
                                @else — @endif
                            </td>
                            @can('booking_view')
                                <td class="text-end text-nowrap">
                                    @if($canManageCallLog)
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <button type="button"
                                                    class="btn btn-sm btn-link p-0 js-edit-call-log-btn"
                                                    title="{{ translate('Edit') }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addCallLogModal"
                                                    data-followup-id="{{ $callLogFollowup->id }}"
                                                    data-party-type="{{ $callLogFollowup->called_party_type ?: \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_CUSTOMER }}"
                                                    data-provider-id="{{ $callLogFollowup->called_provider_id }}"
                                                    data-called-name="{{ $callLogFollowup->called_name }}"
                                                    data-called-number="{{ $callLogFollowup->called_number }}"
                                                    data-called-at="{{ ($callLogFollowup->followup_at ?? $callLogFollowup->date ?? $callLogFollowup->created_at)?->format('Y-m-d\TH:i') }}"
                                                    data-remarks="{{ $callLogFollowup->remarks }}"
                                                    data-has-recording="{{ $callLogFollowup->hasRecording() ? '1' : '0' }}"
                                                    data-recording-name="{{ $callLogFollowup->recording_original_name }}">
                                                <span class="material-icons" aria-hidden="true">edit</span>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-link text-danger p-0 js-delete-call-log-btn"
                                                    title="{{ translate('Delete') }}"
                                                    data-url="{{ route('admin.booking.call-logs.destroy', [$booking->id, $callLogFollowup->id]) }}">
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
                                <td colspan="{{ auth()->user()?->can('booking_view') ? 5 : 4 }}" class="p-0 border-0">
                                    @include('bookingmodule::admin.booking.partials._booking_followup_recording_details_panel', ['followup' => $callLogFollowup, 'booking' => $booking])
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@include('bookingmodule::admin.booking.partials._booking-scheduled-followup-modals', [
    'booking' => $booking,
    'followups' => $activityFollowups,
    'redirectWebPage' => 'details',
    'requiresMandatoryNextFollowup' => $requiresMandatoryNextFollowup ?? $booking->requiresMandatoryNextFollowup(),
    'followupScheduleMinAt' => $followupScheduleMinAt ?? now()->format('Y-m-d\TH:i'),
])

@include('bookingmodule::admin.booking.partials._booking-call-log-modal', [
    'booking' => $booking,
    'customerName' => $customerName,
    'customerPhone' => $customerPhone,
])

@push('script')
<script>
    (function () {
        'use strict';

        if (window.__bookingActivityFiltersBound) {
            return;
        }

        function getInitialActivityFilter(panel) {
            var params = new URLSearchParams(window.location.search);
            var fromQuery = params.get('activity');
            if (fromQuery === 'comment' || fromQuery === 'followup' || fromQuery === 'change' || fromQuery === 'call') {
                return fromQuery;
            }

            if (window.location.hash === '#booking-activity') {
                return 'comment';
            }

            var active = panel.querySelector('.filter-pill.is-active');
            return active ? active.getAttribute('data-activity-filter') : 'comment';
        }

        function activateActivityFilter(panel, filter) {
            if (!panel) {
                return;
            }

            filter = filter || 'comment';

            panel.querySelectorAll('.filter-pill').forEach(function (pill) {
                pill.classList.toggle('is-active', pill.getAttribute('data-activity-filter') === filter);
            });

            panel.querySelectorAll('[data-activity-view]').forEach(function (view) {
                view.classList.toggle('is-visible', view.getAttribute('data-activity-view') === filter);
            });
        }

        function syncBookingActivityPanel(root) {
            var panel = (root || document).querySelector('#booking-activity');
            if (!panel) {
                return;
            }

            activateActivityFilter(panel, getInitialActivityFilter(panel));

            if (window.location.hash === '#booking-activity') {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            var takeFollowupId = new URLSearchParams(window.location.search).get('take');
            if (takeFollowupId) {
                activateActivityFilter(panel, 'followup');
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        document.addEventListener('click', function (event) {
            var pill = event.target.closest('#booking-activity [data-activity-filter]');
            if (!pill) {
                return;
            }

            event.preventDefault();
            activateActivityFilter(
                document.getElementById('booking-activity'),
                pill.getAttribute('data-activity-filter')
            );
        });

        document.addEventListener('DOMContentLoaded', function () {
            syncBookingActivityPanel(document.getElementById('admin-main') || document);
        });

        document.addEventListener('admin:page-loaded', function (event) {
            syncBookingActivityPanel(event.detail && event.detail.root ? event.detail.root : document);
        });

        if (document.readyState !== 'loading') {
            syncBookingActivityPanel(document.getElementById('admin-main') || document);
        }

        var sideReschedule = document.getElementById('booking-schedule-edit-toggle-side');
        var mainReschedule = document.getElementById('booking-schedule-edit-toggle');
        if (sideReschedule && mainReschedule) {
            sideReschedule.addEventListener('click', function () { mainReschedule.click(); });
        }

        window.__bookingActivityFiltersBound = true;
    })();
</script>
@include('bookingmodule::admin.booking.partials._booking-comment-tagging-scripts')
@include('bookingmodule::admin.booking.partials._booking-take-followup-scripts')
@include('bookingmodule::admin.booking.partials._booking-edit-followup-scripts')
@include('bookingmodule::admin.booking.partials._booking-call-log-scripts')
@endpush
