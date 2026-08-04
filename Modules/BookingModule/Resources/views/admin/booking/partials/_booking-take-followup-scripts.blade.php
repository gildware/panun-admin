<script>
    (function () {
        var routesEl = document.getElementById('booking-take-followup-routes');
        var metaEl = document.getElementById('booking-take-followup-meta');
        if (!routesEl || !metaEl) return;

        var followupRoutes = JSON.parse(routesEl.textContent || '{}');
        var followupMeta = JSON.parse(metaEl.textContent || '{}');
        var mandatoryNext = document.getElementById('takeFollowupModal')?.getAttribute('data-mandatory-next') === '1';

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
            scheduledFor: @json(translate('Scheduled_for'))
        };

        var $modal = $('#takeFollowupModal');
        var $form = $('#booking-take-followup-form');

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

        function toggleBookingFollowupRecordingField() {
            var channel = $('#booking-followup-contact-channel').val();
            var $group = $('#booking-followup-recording-group');
            var $input = $('#booking-followup-recording-input');
            var showRecording = channel === labels.callChannel
                && !$('#booking-followup-datetime-group').hasClass('d-none');
            $group.toggleClass('d-none', !showRecording);
            if (!showRecording) $input.val('');
        }

        function toggleOptionalNextFields() {
            if (mandatoryNext) return;
            var action = $('input[name="followup_action"]:checked').val();
            var scheduleChecked = $('#booking-schedule-next-checkbox').is(':checked');
            var showNext = action !== '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}' && scheduleChecked;
            var $nextInput = $('#booking-next-followup-input');
            var $urgencyGroup = $('#booking-followup-urgency-group');
            if (action === '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}') {
                $nextInput.prop('required', true);
                $urgencyGroup.show();
                $('#booking-schedule-next-wrap').hide();
            } else if (mandatoryNext) {
                $nextInput.prop('required', true);
                $urgencyGroup.show();
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
                if (!$('#booking-next-followup-input').val()) {
                    $('#booking-next-followup-input').val($('#booking-next-followup-input').data('default') || '');
                }
            } else {
                $('#booking-followup-current-section-title').text(labels.thisFollowup);
                $('#booking-next-followup-label').html(labels.nextFollowup + (mandatoryNext ? ' <span class="text-danger">*</span>' : ''));
                $('#booking-followup-submit-btn').text(labels.saveChanges);
                toggleBookingFollowupRecordingField();
                toggleOptionalNextFields();
            }

            applyFollowupFutureMin($modal);
        }

        function bindTakeFollowupTriggers() {
            $(document).on('click', '[data-booking-take-followup]', function () {
                var followupId = String($(this).data('followupId') || '');
                var route = followupRoutes[followupId];
                var meta = followupMeta[followupId] || {};
                if (!route) return;

                $form.attr('action', route);
                $('#booking-followup-action-taken').prop('checked', true);
                $('#booking-followup-urgency-select').val(meta.urgency || 'medium');

                var contextParts = [];
                if (meta.for) contextParts.push(labels.forLabel + ': ' + meta.for.charAt(0).toUpperCase() + meta.for.slice(1));
                if (meta.date) contextParts.push(labels.scheduledFor + ': ' + meta.date);
                if (meta.reason) contextParts.push(meta.reason);
                $('#booking-take-followup-context').text(contextParts.join(' · '));

                if (!$('#booking-schedule-next-checkbox').length || !mandatoryNext) {
                    $('#booking-schedule-next-checkbox').prop('checked', mandatoryNext);
                }

                toggleBookingFollowupActionFields();
            });
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
                    if (typeof toastr !== 'undefined') toastr.success(@json(translate('Copied')));
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(function () {
                        var $temp = $('<textarea>').val(text).appendTo('body').select();
                        try { document.execCommand('copy'); done(); } catch (e) {}
                        $temp.remove();
                    });
                }
            });
        }

        function buildTranscriptHtml(transcript) {
            if (!transcript) return '';
            return transcript.split('\n').map(function (line) {
                line = (line || '').trim();
                if (!line) return '';
                var cls = '';
                if (/^User:/i.test(line)) cls = ' voice-call-transcript-line--user';
                else if (/^Support:/i.test(line)) cls = ' voice-call-transcript-line--llm';
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
                    if (!$detailsRow.length) return;

                    var isHidden = $detailsRow.hasClass('d-none');
                    if (isHidden) {
                        $table.find('tr.booking-followup-row.is-open, tr.lead-followup-row.is-open, tr.booking-call-log-row.is-open').removeClass('is-open');
                        $table.find('tr.voice-call-details-row').addClass('d-none');
                        $table.find('.voice-call-details-toggle[aria-expanded="true"]').each(function () {
                            $(this).attr('aria-expanded', 'false').text(@json(translate('View')));
                        });
                        pauseBookingFollowupRecordings($table.find('tr.voice-call-details-row'));
                    }

                    $detailsRow.toggleClass('d-none', !isHidden);
                    $row.toggleClass('is-open', isHidden);
                    $btn.attr('aria-expanded', isHidden ? 'true' : 'false');
                    $btn.text(isHidden ? @json(translate('Hide')) : @json(translate('View')));

                    if (isHidden) bindFollowupCopyButtons($detailsRow);
                    else pauseBookingFollowupRecordings($detailsRow);
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

        $modal.on('show.bs.modal', function () {
            applyFollowupFutureMin($modal);
            toggleBookingFollowupActionFields();
        });

        $(document).on('change', '#booking-followup-contact-channel', toggleBookingFollowupRecordingField);
        $(document).on('change', 'input[name="followup_action"]', toggleBookingFollowupActionFields);
        $(document).on('change', '#booking-schedule-next-checkbox', toggleOptionalNextFields);

        bindTakeFollowupTriggers();
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
            if ($label.length) $label.text(@json(translate('Transcribing')) + '…');

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
                if (typeof toastr !== 'undefined') toastr.error(msg);
            }).always(function () {
                $btn.prop('disabled', false);
                if ($label.length) $label.text(originalLabel);
            });
        });

        var takeId = new URLSearchParams(window.location.search).get('take');
        if (takeId && followupRoutes[takeId]) {
            $('[data-booking-take-followup][data-followup-id="' + takeId + '"]').first().trigger('click');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('takeFollowupModal')).show();
            }
        }
    })();
</script>
