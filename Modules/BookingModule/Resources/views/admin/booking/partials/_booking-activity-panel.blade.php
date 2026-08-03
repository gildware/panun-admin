@php
    $sortedComments = $sortedComments ?? collect();
    $activityFollowups = $activityFollowups ?? collect();
    $changeLogs = $booking->change_logs ?? collect();
    $commentCount = $sortedComments->count();
    $followupCount = $activityFollowups->count();
    $changeCount = $changeLogs->count();
@endphp
<section class="activity-panel" id="booking-activity">
    <div class="activity-toolbar">
        <div class="filter-pills" role="tablist">
            <button class="filter-pill is-active" type="button" data-activity-filter="comment">
                {{ translate('Comments') }} <span class="count">{{ $commentCount }}</span>
            </button>
            <button class="filter-pill" type="button" data-activity-filter="followup">
                {{ translate('Followups') }} <span class="count">{{ $followupCount }}</span>
            </button>
            <button class="filter-pill" type="button" data-activity-filter="change">
                {{ translate('Change_History') }} <span class="count">{{ $changeCount }}</span>
            </button>
        </div>
        <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'followups']) }}" class="btn btn-demo-outline btn-sm">
            <span class="material-icons">event</span>{{ translate('Add_Follow_up') }}
        </a>
    </div>

    <div class="activity-view is-visible" data-activity-view="comment">
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
                  class="comment-compose lead-comment-compose staff-chat-compose-wrap position-relative mt-3">
                @csrf
                <input type="hidden" name="redirect_web_page" value="details">
                @include('leadmanagement::admin.leads.partials._comment-compose')
                <textarea name="body"
                          id="bookingCommentBody"
                          class="form-control form-control-sm staff-chat-message-input lead-comment-compose__input w-100"
                          rows="2"
                          required
                          maxlength="5000"
                          placeholder="{{ translate('Write_a_comment') }}"></textarea>
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

    <div class="activity-view" data-activity-view="followup">
        <div class="activity-table-section activity-table-section--followups">
            @include('bookingmodule::admin.booking.partials._booking-followup-history-table', [
                'booking' => $booking,
                'followups' => $activityFollowups,
                'followupDelayMeta' => $followupDelayMeta ?? [],
                'showActionColumn' => true,
            ])
        </div>
    </div>

    <div class="activity-view" data-activity-view="change">
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
</section>

@include('bookingmodule::admin.booking.partials._booking-scheduled-followup-modals', [
    'booking' => $booking,
    'followups' => $activityFollowups,
    'redirectWebPage' => 'details',
    'requiresMandatoryNextFollowup' => $requiresMandatoryNextFollowup ?? $booking->requiresMandatoryNextFollowup(),
    'followupScheduleMinAt' => $followupScheduleMinAt ?? now()->format('Y-m-d\TH:i'),
])

@push('script')
<script>
    (function () {
        var panel = document.getElementById('booking-activity');
        if (!panel) return;
        panel.querySelectorAll('[data-activity-filter]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var filter = btn.getAttribute('data-activity-filter');
                panel.querySelectorAll('.filter-pill').forEach(function (p) { p.classList.remove('is-active'); });
                btn.classList.add('is-active');
                panel.querySelectorAll('[data-activity-view]').forEach(function (view) {
                    view.classList.toggle('is-visible', view.getAttribute('data-activity-view') === filter);
                });
            });
        });

        function activateActivityFilter(filter) {
            var targetBtn = panel.querySelector('[data-activity-filter="' + filter + '"]');
            if (targetBtn) {
                targetBtn.click();
            }
        }

        if (window.location.hash === '#booking-activity') {
            activateActivityFilter('comment');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        var takeFollowupId = new URLSearchParams(window.location.search).get('take');
        if (takeFollowupId && panel) {
            activateActivityFilter('followup');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        var sideReschedule = document.getElementById('booking-schedule-edit-toggle-side');
        var mainReschedule = document.getElementById('booking-schedule-edit-toggle');
        if (sideReschedule && mainReschedule) {
            sideReschedule.addEventListener('click', function () { mainReschedule.click(); });
        }
    })();
</script>
@include('bookingmodule::admin.booking.partials._booking-comment-tagging-scripts')
@include('bookingmodule::admin.booking.partials._booking-take-followup-scripts')
@endpush
