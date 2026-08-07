<script>
    (function () {
        var modalEl = document.getElementById('takeFollowupModal');
        var formEl = document.getElementById('booking-take-followup-form');
        if (!modalEl || !formEl) {
            return;
        }

        var routesEl = document.getElementById('booking-take-followup-routes');
        var metaEl = document.getElementById('booking-take-followup-meta');
        var followupRoutes = {};
        var followupMeta = {};
        try {
            followupRoutes = routesEl ? JSON.parse(routesEl.textContent || '{}') : {};
        } catch (e) {
            followupRoutes = {};
        }
        try {
            followupMeta = metaEl ? JSON.parse(metaEl.textContent || '{}') : {};
        } catch (e) {
            followupMeta = {};
        }

        var labels = {
            take: @json(translate('Take_Follow_up')),
            saveChanges: @json(translate('Save_changes')),
            nextFollowup: @json(translate('Next_Follow_up_Date')),
            rescheduleTo: @json(translate('Reschedule_to')),
            thisFollowup: @json(translate('This_Follow_up')),
            rescheduleDetails: @json(translate('Reschedule_Details')),
            reschedule: @json(translate('Reschedule')),
            callChannel: @json(\Modules\BookingModule\Entities\BookingFollowup::CHANNEL_CALL),
            forLabel: @json(translate('For')),
            scheduledFor: @json(translate('Scheduled_for')),
            failedToUpdate: @json(translate('Failed_to_update')),
            remarksRequired: @json(translate('Follow_up_remarks_required')),
            nextDateRequired: @json(translate('Next_follow_up_date_is_required')),
            nextDateFuture: @json(translate('Reschedule_date_must_be_in_the_future')),
            takenAtFuture: @json(translate('Follow_up_taken_at_cannot_be_in_the_future')),
            saving: @json(translate('Save_changes')) + '…'
        };

        function showTakeFollowupError(message) {
            if (typeof toastr !== 'undefined') {
                toastr.error(message);
            } else if (window.console && console.error) {
                console.error(message);
            }
        }

        function resolveTakeFollowupAction(trigger) {
            var followupId = '';
            var route = '';
            if (trigger) {
                followupId = trigger.getAttribute('data-followup-id') || '';
                route = trigger.getAttribute('data-followup-update-url') || '';
            }
            if (!followupId) {
                var idInput = document.getElementById('booking-followup-id-input');
                followupId = idInput ? String(idInput.value || '') : '';
            }
            if (!route && followupId && followupRoutes[followupId]) {
                route = followupRoutes[followupId];
            }
            return { followupId: followupId, route: (route || '').trim() };
        }

        function applyTakeFollowupAction(trigger) {
            var form = document.getElementById('booking-take-followup-form');
            if (!form) {
                return false;
            }
            var resolved = resolveTakeFollowupAction(trigger);
            if (!resolved.route) {
                // Keep an already-correct action if routes map is empty.
                var existing = (form.getAttribute('action') || '').trim();
                if (existing && existing.indexOf('/followup/') !== -1) {
                    return true;
                }
                return false;
            }
            form.setAttribute('action', resolved.route);
            form.action = resolved.route;
            var idInput = document.getElementById('booking-followup-id-input');
            if (idInput && resolved.followupId) {
                idInput.value = resolved.followupId;
            }
            return true;
        }

        // Vanilla capture listener — works even if jQuery is not ready yet (partial nav).
        if (!window.__bookingTakeFollowupActionBound) {
            window.__bookingTakeFollowupActionBound = true;
            document.addEventListener('click', function (event) {
                var trigger = event.target && event.target.closest
                    ? event.target.closest('[data-booking-take-followup]')
                    : null;
                if (!trigger) {
                    return;
                }
                applyTakeFollowupAction(trigger);
            }, true);

            document.addEventListener('submit', function (event) {
                if (!event.target || event.target.id !== 'booking-take-followup-form') {
                    return;
                }
                applyTakeFollowupAction(null);
                var action = (event.target.getAttribute('action') || event.target.action || '').trim();
                // Ignore same-page / empty actions that would POST to booking details.
                if (!action || action === window.location.href || action === window.location.pathname) {
                    event.preventDefault();
                    event.stopPropagation();
                    showTakeFollowupError(labels.failedToUpdate);
                }
            }, true);
        }

        function bootTakeFollowupScripts() {
            if (!window.jQuery) {
                return false;
            }

            var $ = window.jQuery;
            var $modal = $('#takeFollowupModal');
            var $form = $('#booking-take-followup-form');
            if (!$modal.length || !$form.length) {
                return false;
            }

            // Re-bind per modal instance (partial nav replaces the DOM).
            if ($modal.data('bookingTakeBound')) {
                return true;
            }
            $modal.data('bookingTakeBound', 1);

            var mandatoryNext = ($modal[0] && $modal[0].getAttribute('data-mandatory-next') === '1')
                || modalEl.getAttribute('data-mandatory-next') === '1';

        function readTriggerAttr(trigger, name) {
            return trigger ? (trigger.getAttribute(name) || '') : '';
        }

        function configureTakeFollowupModal(trigger, resetTakenFields) {
            if (!trigger) {
                return false;
            }

            if (!applyTakeFollowupAction(trigger)) {
                return false;
            }

            var followupId = readTriggerAttr(trigger, 'data-followup-id');

            var meta = followupMeta[followupId] || {};
            var forParty = readTriggerAttr(trigger, 'data-followup-for') || meta.for || '';
            var scheduledDate = readTriggerAttr(trigger, 'data-followup-date') || meta.date || '';
            var reason = readTriggerAttr(trigger, 'data-followup-reason') || meta.reason || '';
            var urgency = readTriggerAttr(trigger, 'data-followup-urgency') || meta.urgency || 'medium';

            if (resetTakenFields) {
                $('#booking-followup-action-taken').prop('checked', true);
                $('#booking-followup-urgency-select').val(urgency);
                $('#booking-followup-at-input').val(localFollowupScheduleMin());
                $('#booking-followup-remarks-input').val('');
                $('#booking-followup-recording-input').val('');
                $('#booking-followup-contact-channel').val(labels.callChannel);
                var $nextInput = $('#booking-next-followup-input');
                $nextInput.val($nextInput.data('default') || '');
                if (!$('#booking-schedule-next-checkbox').length || !mandatoryNext) {
                    $('#booking-schedule-next-checkbox').prop('checked', mandatoryNext);
                }
            }

            var contextParts = [];
            if (forParty) {
                contextParts.push(labels.forLabel + ': ' + forParty.charAt(0).toUpperCase() + forParty.slice(1));
            }
            if (scheduledDate) {
                contextParts.push(labels.scheduledFor + ': ' + scheduledDate);
            }
            if (reason) {
                contextParts.push(reason);
            }
            $('#booking-take-followup-context').text(contextParts.join(' · '));

            toggleBookingFollowupActionFields();
            return true;
        }

        function localFollowupScheduleMin() {
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            return now.toISOString().slice(0, 16);
        }

        function applyFollowupFutureMin($root) {
            var min = localFollowupScheduleMin();
            ($root && $root.length ? $root : $(document)).find('input.js-followup-future-only').each(function () {
                this.min = min;
            });
        }

        function refreshFollowupNotFutureMax(input, clampValue) {
            if (!input) {
                return localFollowupScheduleMin();
            }
            var max = localFollowupScheduleMin();
            input.max = max;
            if (clampValue && input.value && input.value > max) {
                input.value = max;
            }
            return max;
        }

        function applyFollowupNotFutureMax($root, clampValue) {
            ($root && $root.length ? $root : $(document)).find('input.js-followup-not-future').each(function () {
                refreshFollowupNotFutureMax(this, !!clampValue);
            });
        }

        function isTakenAtInFuture(input) {
            if (!input || !input.value) {
                return false;
            }
            var max = refreshFollowupNotFutureMax(input, false);
            return input.value > max;
        }

        function toggleBookingFollowupRecordingField() {
            var channel = $('#booking-followup-contact-channel').val();
            var $group = $('#booking-followup-recording-group');
            var $input = $('#booking-followup-recording-input');
            var showRecording = channel === labels.callChannel
                && !$('#booking-followup-datetime-group').hasClass('d-none');
            $group.toggleClass('d-none', !showRecording);
            if (!showRecording) {
                $input.val('');
            }
        }

        function toggleOptionalNextFields() {
            if (mandatoryNext) {
                return;
            }

            var action = $('input[name="followup_action"]:checked').val();
            var scheduleChecked = $('#booking-schedule-next-checkbox').is(':checked');
            var showNext = action !== '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}' && scheduleChecked;
            var $nextInput = $('#booking-next-followup-input');
            var $urgencyGroup = $('#booking-followup-urgency-group');

            if (action === '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}') {
                $nextInput.prop('required', true);
                $urgencyGroup.show();
                $('#booking-schedule-next-wrap').hide();
            } else {
                $('#booking-schedule-next-wrap').show();
                $nextInput.prop('required', showNext);
                $urgencyGroup.toggle(showNext || false);
                if (!scheduleChecked) {
                    $nextInput.val('');
                } else if (!$nextInput.val()) {
                    $nextInput.val($nextInput.data('default') || '');
                }
            }
        }

        function toggleBookingFollowupActionFields() {
            var action = $('input[name="followup_action"]:checked').val() || '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_TAKEN }}';
            var isReschedule = action === '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}';

            $('#booking-followup-datetime-group, #booking-followup-channel-group').toggleClass('d-none', isReschedule);
            $('#booking-followup-at-input').prop('required', !isReschedule);

            if (isReschedule) {
                $('#booking-followup-recording-input').val('');
                $('#booking-followup-recording-group').addClass('d-none');
                $('#booking-followup-current-section-title').text(labels.rescheduleDetails);
                $('#booking-next-followup-label').html(labels.rescheduleTo + ' <span class="text-danger">*</span>');
                $('#booking-followup-submit-btn').text(labels.reschedule);
                $('#booking-followup-urgency-group').show();
                $('#booking-next-followup-input').prop('required', true);
                $('#booking-followup-remarks-input').prop('required', false);
                $('#booking-followup-remarks-label .text-danger').addClass('d-none');
                if (!$('#booking-next-followup-input').val()) {
                    $('#booking-next-followup-input').val($('#booking-next-followup-input').data('default') || '');
                }
            } else {
                $('#booking-followup-current-section-title').text(labels.thisFollowup);
                $('#booking-next-followup-label').html(labels.nextFollowup + (mandatoryNext ? ' <span class="text-danger">*</span>' : ''));
                $('#booking-followup-submit-btn').text(labels.saveChanges);
                $('#booking-followup-remarks-input').prop('required', true);
                $('#booking-followup-remarks-label .text-danger').removeClass('d-none');
                toggleBookingFollowupRecordingField();
                toggleOptionalNextFields();
            }

            applyFollowupFutureMin($modal);
            applyFollowupNotFutureMax($modal, true);
        }

        function bindFollowupCopyButtons($scope) {
            $scope.find('.voice-call-copy-btn[data-copy-b64]').off('click.bookingFollowupCopy').on('click.bookingFollowupCopy', function () {
                var encoded = $(this).attr('data-copy-b64') || '';
                var text = '';
                try {
                    text = atob(encoded);
                } catch (e) {
                    return;
                }
                var done = function () {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(@json(translate('Copied')));
                    }
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(function () {
                        var $temp = $('<textarea>').val(text).appendTo('body').select();
                        try { document.execCommand('copy'); done(); } catch (err) {}
                        $temp.remove();
                    });
                }
            });
        }

        function buildTranscriptHtml(transcript) {
            if (!transcript) {
                return '';
            }

            return transcript.split('\n').map(function (line) {
                line = (line || '').trim();
                if (!line) {
                    return '';
                }
                var cls = '';
                if (/^User:/i.test(line)) {
                    cls = ' voice-call-transcript-line--user';
                } else if (/^Support:/i.test(line)) {
                    cls = ' voice-call-transcript-line--llm';
                }
                return '<div class="voice-call-transcript-line' + cls + '">' + $('<div>').text(line).html() + '</div>';
            }).join('');
        }

        function pauseBookingFollowupRecordings($scope) {
            $scope.find('.voice-call-audio-player, audio').each(function () {
                try { this.pause(); } catch (e) {}
            });
        }

        function bindBookingFollowupDetailToggles() {
            $(document).off('click.bookingFollowupDetails', '.booking-followup-history-table .voice-call-details-toggle, .lead-followup-history-table .voice-call-details-toggle, .booking-call-log-table .voice-call-details-toggle')
                .on('click.bookingFollowupDetails', '.booking-followup-history-table .voice-call-details-toggle, .lead-followup-history-table .voice-call-details-toggle, .booking-call-log-table .voice-call-details-toggle', function () {
                    var $btn = $(this);
                    var $row = $btn.closest('tr');
                    var $table = $btn.closest('.booking-followup-history-table, .lead-followup-history-table, .booking-call-log-table');
                    var $detailsRow = $row.next('tr.voice-call-details-row');
                    if (!$detailsRow.length) {
                        return;
                    }

                    var isHidden = $detailsRow.hasClass('d-none');
                    if (isHidden) {
                        $table.find('tr.booking-followup-row.is-open, tr.lead-followup-row.is-open, tr.booking-call-log-row.is-open').removeClass('is-open');
                        $table.find('tr.voice-call-details-row').addClass('d-none');
                        $table.find('.voice-call-details-toggle[aria-expanded="true"]').each(function () {
                            var $toggle = $(this);
                            $toggle.attr('aria-expanded', 'false');
                            var $icon = $toggle.find('.js-followup-view-icon');
                            if ($icon.length) {
                                $icon.text('visibility');
                                $toggle.attr('title', @json(translate('View')));
                                $toggle.attr('aria-label', @json(translate('View')));
                            } else {
                                $toggle.text(@json(translate('View')));
                            }
                        });
                        pauseBookingFollowupRecordings($table.find('tr.voice-call-details-row'));
                    }

                    $detailsRow.toggleClass('d-none', !isHidden);
                    $row.toggleClass('is-open', isHidden);
                    $btn.attr('aria-expanded', isHidden ? 'true' : 'false');

                    var $viewIcon = $btn.find('.js-followup-view-icon');
                    if ($viewIcon.length) {
                        $viewIcon.text(isHidden ? 'visibility_off' : 'visibility');
                        $btn.attr('title', isHidden ? @json(translate('Hide')) : @json(translate('View')));
                        $btn.attr('aria-label', isHidden ? @json(translate('Hide')) : @json(translate('View')));
                    } else {
                        $btn.text(isHidden ? @json(translate('Hide')) : @json(translate('View')));
                    }

                    if (isHidden) {
                        bindFollowupCopyButtons($detailsRow);
                    } else {
                        pauseBookingFollowupRecordings($detailsRow);
                    }
                });
        }

        function renderBookingFollowupTranscriptPanel($panel, data) {
            $panel.find('.followup-recording-summary').text(data.summary || @json(translate('No_call_summary_available')));
            var $transcriptCard = $panel.find('.voice-call-detail-box').last();
            $transcriptCard.find('.card-body')
                .removeClass('p-3')
                .addClass('p-0')
                .html('<div class="voice-call-transcript followup-recording-transcript-wrap">' + buildTranscriptHtml(data.transcript || '') + '</div>');

            var $meta = $panel.find('.followup-transcript-meta');
            if (data.transcribed_at) {
                $meta.removeClass('d-none').text(@json(translate('Transcribed_by')) + ' ' + @json(translate('Google_Gemini_AI')));
            }

            $panel.find('.js-transcribe-booking-followup-recording[data-has-transcript="0"]').remove();
            bindFollowupCopyButtons($panel);
        }

        function validateTakeFollowupForm() {
            var action = ($form.attr('action') || '').trim();
            if (!action) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(labels.failedToUpdate);
                }
                return false;
            }

            applyFollowupNotFutureMax($modal, false);

            var followupAction = $('input[name="followup_action"]:checked').val();
            if (followupAction !== '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}') {
                var takenAtInput = document.getElementById('booking-followup-at-input');
                var takenAt = (takenAtInput && takenAtInput.value) ? takenAtInput.value.trim() : '';
                if (!takenAt) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(@json(translate('Follow_up_taken_at_is_required')));
                    }
                    $('#booking-followup-at-input').focus();
                    return false;
                }
                if (isTakenAtInFuture(takenAtInput)) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(labels.takenAtFuture);
                    }
                    $('#booking-followup-at-input').focus();
                    return false;
                }
            }

            var remarks = ($('#booking-followup-remarks-input').val() || '').trim();
            if (followupAction !== '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}' && remarks === '') {
                if (typeof toastr !== 'undefined') {
                    toastr.error(labels.remarksRequired);
                }
                $('#booking-followup-remarks-input').focus();
                return false;
            }

            if (mandatoryNext || followupAction === '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}' || $('#booking-schedule-next-checkbox').is(':checked')) {
                var nextVal = ($('#booking-next-followup-input').val() || '').trim();
                if (!nextVal) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(labels.nextDateRequired);
                    }
                    $('#booking-next-followup-input').focus();
                    return false;
                }

                var nextInput = document.getElementById('booking-next-followup-input');
                if (nextInput && nextInput.min && nextVal < nextInput.min) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(labels.nextDateFuture);
                    }
                    $('#booking-next-followup-input').focus();
                    return false;
                }
            }

            return true;
        }

        $modal.on('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            var isReopen = modalEl.getAttribute('data-reopen-on-load') === '1';

            if (!trigger && isReopen) {
                var storedFollowupId = String($('#booking-followup-id-input').val() || '');
                trigger = storedFollowupId
                    ? document.querySelector('[data-booking-take-followup][data-followup-id="' + storedFollowupId + '"]')
                    : null;

                if (!trigger && storedFollowupId && followupRoutes[storedFollowupId]) {
                    $form.attr('action', followupRoutes[storedFollowupId]);
                    toggleBookingFollowupActionFields();
                    applyFollowupFutureMin($modal);
                    applyFollowupNotFutureMax($modal, true);
                    return;
                }
            }

            if (!configureTakeFollowupModal(trigger, !isReopen)) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(labels.failedToUpdate);
                }
                event.preventDefault();
                return;
            }

            applyFollowupFutureMin($modal);
            applyFollowupNotFutureMax($modal, true);
        });

        $modal.on('hidden.bs.modal', function () {
            modalEl.removeAttribute('data-reopen-on-load');
        });

        $(document).on('change input', '#booking-followup-at-input', function () {
            if (isTakenAtInFuture(this)) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(labels.takenAtFuture);
                }
                refreshFollowupNotFutureMax(this, true);
            } else {
                refreshFollowupNotFutureMax(this, false);
            }
        });

        $(document).on('change', '#booking-followup-contact-channel', toggleBookingFollowupRecordingField);
        $(document).on('change', 'input[name="followup_action"]', toggleBookingFollowupActionFields);
        $(document).on('change', '#booking-schedule-next-checkbox', toggleOptionalNextFields);

        bindBookingFollowupDetailToggles();
        bindFollowupCopyButtons($('.booking-followup-history-table, .lead-followup-history-table'));

        $(document).on('click', '.js-transcribe-booking-followup-recording', function () {
            var $btn = $(this);
            var followupId = $btn.data('followup-id');
            var url = $btn.data('url');
            var $panel = $('#booking-followup-transcript-panel-' + followupId);
            var $label = $btn.find('.js-transcribe-btn-label');
            var originalLabel = $label.length ? $label.text() : $btn.text();

            $btn.prop('disabled', true);
            if ($label.length) {
                $label.text(@json(translate('Transcribing')) + '…');
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: @json(csrf_token()),
                    force: $btn.data('force') ? 1 : 0
                }
            }).done(function (response) {
                renderBookingFollowupTranscriptPanel($panel, response);
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : @json(translate('Failed_to_transcribe_recording'));
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                }
            }).always(function () {
                $btn.prop('disabled', false);
                if ($label.length) {
                    $label.text(originalLabel);
                }
            });
        });

        $form.on('submit', function (event) {
            applyTakeFollowupAction(null);
            var action = ($form.attr('action') || '').trim();
            if (!action) {
                event.preventDefault();
                showTakeFollowupError(labels.failedToUpdate);
                return;
            }

            if (!validateTakeFollowupForm()) {
                event.preventDefault();
            }
        });

        var liveModalEl = $modal[0] || modalEl;

        var takeId = new URLSearchParams(window.location.search).get('take');
        if (takeId) {
            var takeBtn = document.querySelector('[data-booking-take-followup][data-followup-id="' + takeId + '"]');
            if (takeBtn && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                applyTakeFollowupAction(takeBtn);
                bootstrap.Modal.getOrCreateInstance(liveModalEl).show(takeBtn);
            }
        }

        if (liveModalEl.getAttribute('data-reopen-on-load') === '1') {
            var storedFollowupId = String($('#booking-followup-id-input').val() || '');
            var reopenTrigger = storedFollowupId
                ? document.querySelector('[data-booking-take-followup][data-followup-id="' + storedFollowupId + '"]')
                : null;

            if (reopenTrigger && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                applyTakeFollowupAction(reopenTrigger);
                bootstrap.Modal.getOrCreateInstance(liveModalEl).show(reopenTrigger);
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                applyTakeFollowupAction(null);
                bootstrap.Modal.getOrCreateInstance(liveModalEl).show();
            }
        }

        @if($errors->has('followup_at') && old('followup_mode') === 'take')
        if (typeof toastr !== 'undefined') {
            toastr.error(@json($errors->first('followup_at')));
        }
        @endif

            return true;
        }

        if (!bootTakeFollowupScripts()) {
            document.addEventListener('DOMContentLoaded', function () {
                bootTakeFollowupScripts();
            });
            document.addEventListener('admin:page-loaded', function () {
                bootTakeFollowupScripts();
            });
            window.addEventListener('load', function () {
                bootTakeFollowupScripts();
            });
        } else {
            document.addEventListener('admin:page-loaded', function () {
                bootTakeFollowupScripts();
            });
        }
    })();
</script>
