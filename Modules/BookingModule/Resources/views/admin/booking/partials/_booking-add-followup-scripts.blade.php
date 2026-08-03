<script>
    (function () {
        var modalEl = document.getElementById('addFollowupModal');
        if (!modalEl) return;

        var mandatoryNext = modalEl.getAttribute('data-mandatory-next') === '1';
        var labels = {
            saveChanges: @json(translate('Save_changes')),
            nextFollowup: @json(translate('Next_Follow_up_Date')),
            rescheduleTo: @json(translate('Reschedule_to')),
            thisFollowup: @json(translate('This_Follow_up')),
            rescheduleDetails: @json(translate('Reschedule_Details')),
            reschedule: @json(translate('Reschedule')),
            callChannel: @json(\Modules\BookingModule\Entities\BookingFollowup::CHANNEL_CALL),
        };

        var $modal = $('#addFollowupModal');

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

        function toggleAddFollowupRecordingField() {
            var channel = $('#booking-add-followup-contact-channel').val();
            var $group = $('#booking-add-followup-recording-group');
            var $input = $('#booking-add-followup-recording-input');
            var showRecording = channel === labels.callChannel
                && !$('#booking-add-followup-datetime-group').hasClass('d-none');
            $group.toggleClass('d-none', !showRecording);
            if (!showRecording) {
                $input.val('');
            }
        }

        function toggleOptionalNextFields() {
            if (mandatoryNext) {
                return;
            }
            var action = $('input[name="followup_action"]:checked', '#booking-add-followup-form').val();
            var scheduleChecked = $('#booking-add-schedule-next-checkbox').is(':checked');
            var showNext = action !== '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}' && scheduleChecked;
            var $nextInput = $('#booking-add-next-followup-input');
            var $urgencyGroup = $('#booking-add-followup-urgency-group');

            if (action === '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}') {
                $nextInput.prop('required', true);
                $urgencyGroup.show();
                $('#booking-add-schedule-next-wrap').hide();
            } else {
                $('#booking-add-schedule-next-wrap').show();
                $nextInput.prop('required', showNext);
                $urgencyGroup.toggle(showNext || false);
                if (!scheduleChecked) {
                    $nextInput.val('');
                } else if (!$nextInput.val()) {
                    $nextInput.val($nextInput.data('default') || '');
                }
            }
        }

        function toggleAddFollowupActionFields() {
            var action = $('input[name="followup_action"]:checked', '#booking-add-followup-form').val()
                || '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_TAKEN }}';
            var isReschedule = action === '{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}';

            $('#booking-add-followup-datetime-group, #booking-add-followup-channel-group').toggleClass('d-none', isReschedule);
            $('#booking-add-followup-at-input').prop('required', !isReschedule);

            if (isReschedule) {
                $('#booking-add-followup-recording-input').val('');
                $('#booking-add-followup-recording-group').addClass('d-none');
                $('#booking-add-followup-current-section-title').text(labels.rescheduleDetails);
                $('#booking-add-next-followup-label').html(labels.rescheduleTo + ' <span class="text-danger">*</span>');
                $('#booking-add-followup-submit-btn').text(labels.reschedule);
                $('#booking-add-followup-urgency-group').show();
                $('#booking-add-next-followup-input').prop('required', true);
                if (!$('#booking-add-next-followup-input').val()) {
                    $('#booking-add-next-followup-input').val($('#booking-add-next-followup-input').data('default') || '');
                }
            } else {
                $('#booking-add-followup-current-section-title').text(labels.thisFollowup);
                $('#booking-add-next-followup-label').html(labels.nextFollowup + (mandatoryNext ? ' <span class="text-danger">*</span>' : ''));
                $('#booking-add-followup-submit-btn').text(labels.saveChanges);
                toggleAddFollowupRecordingField();
                toggleOptionalNextFields();
            }

            applyFollowupFutureMin($modal);
        }

        $modal.on('show.bs.modal', function () {
            $('#booking-add-followup-action-taken').prop('checked', true);
            applyFollowupFutureMin($modal);
            toggleAddFollowupActionFields();
        });

        $(document).on('change', '#booking-add-followup-contact-channel', toggleAddFollowupRecordingField);
        $(document).on('change', '#booking-add-followup-form input[name="followup_action"]', toggleAddFollowupActionFields);
        $(document).on('change', '#booking-add-schedule-next-checkbox', toggleOptionalNextFields);
    })();
</script>
