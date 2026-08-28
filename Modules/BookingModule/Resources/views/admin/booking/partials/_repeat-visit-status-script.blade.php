<script>
    (function () {
        if (window.__repeatVisitStatusScriptBound) {
            return;
        }
        window.__repeatVisitStatusScriptBound = true;

        var csrf = $('meta[name="csrf-token"]').attr('content');
        var failMsg = @json(translate('Failed to update status'));
        var confirmTitle = @json(translate('are_you_sure'));
        var confirmText = @json(translate('want_to_update_status'));
        var yesText = @json(translate('Yes'));
        var cancelText = @json(translate('Cancel'));

        function repeatVisitStatusErrorMessage(xhr) {
            if (!xhr) {
                return failMsg;
            }
            var json = xhr.responseJSON;
            if (json && json.message) {
                return json.message;
            }
            if (xhr.status === 422 && json && json.errors) {
                var first = Object.values(json.errors)[0];
                if (Array.isArray(first) && first[0]) {
                    return first[0];
                }
            }
            return failMsg;
        }

        function repeatVisitPostStatus(url, status) {
            return $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: csrf,
                    booking_status: status
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                }
            });
        }

        window.repeatVisitApplyStatus = function (status, previous, url) {
            if (!status || status === '0' || !url) {
                return;
            }
            if (typeof bookingAdminStatusNeedsReason === 'function' && bookingAdminStatusNeedsReason(status, previous)) {
                if (typeof bookingAdminOpenStatusReasonModal === 'function') {
                    bookingAdminOpenStatusReasonModal(status, previous, url);
                } else {
                    toastr.error(failMsg);
                }
                return;
            }
            if (typeof Swal === 'undefined') {
                return;
            }
            Swal.fire({
                title: confirmTitle + '?',
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: yesText,
                cancelButtonText: cancelText
            }).then(function (result) {
                if (!result.value) {
                    return;
                }
                repeatVisitPostStatus(url, status).done(function () {
                    window.location.reload();
                }).fail(function (xhr) {
                    toastr.error(repeatVisitStatusErrorMessage(xhr));
                });
            });
        };

        $(document).on('change', '.js-repeat-visit-status', function () {
            var $sel = $(this);
            var status = $sel.val();
            var previous = $sel.attr('data-current');
            var url = $sel.attr('data-status-url');
            if (!status || status === '0') {
                return;
            }
            window.repeatVisitApplyStatus(status, previous, url);
            $sel.val('0');
        });

        $(document).on('click', '.js-repeat-visit-status-btn:not(:disabled)', function () {
            var $btn = $(this);
            window.repeatVisitApplyStatus(
                $btn.attr('data-status'),
                $btn.attr('data-current'),
                $btn.attr('data-status-url')
            );
        });
    })();
</script>
