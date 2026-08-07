<script>
    (function ($) {
        'use strict';

        var $modal = $('#editFollowupModal');
        var $form = $('#booking-edit-followup-form');
        var $deleteModal = $('#deleteFollowupModal');
        var $deleteLabel = $('#deleteFollowupModalItem');
        var $deleteConfirmBtn = $('#deleteFollowupConfirmBtn');
        var pendingDeleteUrl = '';
        var $pendingDeleteBtn = null;

        var labels = {
            status: @json(translate('Status')),
            scheduled: @json(translate('Scheduled')),
            taken: @json(translate('Taken')),
            reschedule: @json(translate('Reschedule')),
            cancelled: @json(translate('Cancelled')),
            failed: @json(translate('Failed_to_update')),
            deleting: @json(translate('Delete')) + '…',
            cannotDeletePending: @json(translate('Cannot_delete_pending_follow_up')),
        };

        function statusLabel(status) {
            if (status === 'completed') return labels.taken;
            if (status === 'rescheduled') return labels.reschedule;
            if (status === 'cancelled') return labels.cancelled;
            return labels.scheduled;
        }

        function toggleEditFollowupFields(status) {
            var isScheduled = status === 'scheduled';
            $('#booking-edit-followup-at-group').toggleClass('d-none', isScheduled);
            $('#booking-edit-followup-channel-group').toggleClass('d-none', isScheduled);
            $('#booking-edit-followup-next-group').toggleClass('d-none', isScheduled);
            $('#booking-edit-followup-reason-group').toggleClass('d-none', !isScheduled);
            $('#booking-edit-followup-at').prop('required', !isScheduled && status === 'completed');
        }

        function configureEditFollowupModal(payload) {
            if (!payload || !payload.url) {
                return false;
            }

            $form.attr('action', payload.url);
            $('#booking-edit-followup-id-input').val(payload.id || '');
            $('#booking-edit-followup-status-label').text(labels.status + ': ' + statusLabel(payload.status || 'scheduled'));
            $('#booking-edit-followup-for').val(payload.for || 'customer');
            $('#booking-edit-followup-date').val(payload.date || '');
            $('#booking-edit-followup-at').val(payload.followupAt || '');
            $('#booking-edit-followup-channel').val(payload.channel || '');
            $('#booking-edit-followup-urgency').val(payload.urgency || 'medium');
            $('#booking-edit-followup-reason').val(payload.reason || '');
            $('#booking-edit-followup-remarks').val(payload.remarks || '');
            $('#booking-edit-followup-next').val(payload.nextAt || '');
            toggleEditFollowupFields(payload.status || 'scheduled');

            $modal.find('input.js-followup-not-future').each(function () {
                var max = (function () {
                    var now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    return now.toISOString().slice(0, 16);
                })();
                this.max = max;
                if (this.value && this.value > max) {
                    this.value = max;
                }
            });
            return true;
        }

        function isEditTakenAtInFuture(input) {
            if (!input || !input.value) {
                return false;
            }
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            var max = now.toISOString().slice(0, 16);
            input.max = max;
            return input.value > max;
        }

        function readEditPayload($btn) {
            return {
                id: $btn.data('followup-id'),
                url: $btn.data('url'),
                status: $btn.data('status'),
                for: $btn.data('for'),
                date: $btn.data('date'),
                followupAt: $btn.data('followup-at'),
                channel: $btn.data('channel'),
                urgency: $btn.data('urgency'),
                reason: $btn.data('reason'),
                remarks: $btn.data('remarks'),
                nextAt: $btn.data('next-at'),
            };
        }

        $(document).on('click', '.js-edit-booking-followup-btn', function () {
            configureEditFollowupModal(readEditPayload($(this)));
        });

        $modal.on('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger || !$(trigger).hasClass('js-edit-booking-followup-btn')) {
                return;
            }
            if (!configureEditFollowupModal(readEditPayload($(trigger)))) {
                event.preventDefault();
                if (typeof toastr !== 'undefined') {
                    toastr.error(labels.failed);
                }
            }
        });

        $form.on('submit', function (event) {
            var action = ($form.attr('action') || '').trim();
            if (!action) {
                event.preventDefault();
                if (typeof toastr !== 'undefined') {
                    toastr.error(labels.failed);
                }
                return;
            }

            var takenAtInput = document.getElementById('booking-edit-followup-at');
            if (takenAtInput && !$('#booking-edit-followup-at-group').hasClass('d-none') && takenAtInput.value && isEditTakenAtInFuture(takenAtInput)) {
                event.preventDefault();
                if (typeof toastr !== 'undefined') {
                    toastr.error(@json(translate('Follow_up_taken_at_cannot_be_in_the_future')));
                }
                takenAtInput.focus();
            }
        });

        $(document).on('change input', '#booking-edit-followup-at', function () {
            if (isEditTakenAtInFuture(this)) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(@json(translate('Follow_up_taken_at_cannot_be_in_the_future')));
                }
                var now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                this.value = now.toISOString().slice(0, 16);
            }
        });

        function applyDeleteTarget(button) {
            if (!button) {
                return;
            }
            pendingDeleteUrl = button.getAttribute('data-url') || '';
            $pendingDeleteBtn = $(button);
            if ($deleteLabel.length) {
                $deleteLabel.text(button.getAttribute('data-label') || '');
            }
        }

        $(document).on('click', '.js-delete-booking-followup-btn', function () {
            applyDeleteTarget(this);
        });

        $deleteModal.on('show.bs.modal', function (event) {
            if (event.relatedTarget) {
                applyDeleteTarget(event.relatedTarget);
            }
            $deleteConfirmBtn.prop('disabled', false).text(@json(translate('Delete')));
        });

        $deleteModal.on('hidden.bs.modal', function () {
            pendingDeleteUrl = '';
            $pendingDeleteBtn = null;
            if ($deleteLabel.length) {
                $deleteLabel.text('');
            }
            $deleteConfirmBtn.prop('disabled', false).text(@json(translate('Delete')));
        });

        $deleteConfirmBtn.on('click', function () {
            if (!pendingDeleteUrl) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(labels.failed);
                }
                return;
            }

            var $btn = $deleteConfirmBtn;
            var original = @json(translate('Delete'));
            $btn.prop('disabled', true).text(labels.deleting);
            if ($pendingDeleteBtn && $pendingDeleteBtn.length) {
                $pendingDeleteBtn.prop('disabled', true);
            }

            fetch(pendingDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).then(function (response) {
                return response.json().catch(function () {
                    return {};
                }).then(function (data) {
                    if (!response.ok) {
                        var msg = (data && data.message) ? data.message : labels.failed;
                        throw new Error(msg);
                    }
                    window.location.reload();
                });
            }).catch(function (err) {
                $btn.prop('disabled', false).text(original);
                if ($pendingDeleteBtn && $pendingDeleteBtn.length) {
                    $pendingDeleteBtn.prop('disabled', false);
                }
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance($deleteModal[0]).hide();
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(err && err.message ? err.message : labels.failed);
                }
            });
        });
    })(jQuery);
</script>
