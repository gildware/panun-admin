<div class="modal fade" id="deleteBookingCommentModal" tabindex="-1" aria-labelledby="deleteBookingCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body pt-5 p-md-5">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                <div class="d-flex justify-content-center mb-4">
                    <img width="75" height="75"
                         src="{{ asset('assets/admin-module/img/media/delete.png') }}"
                         class="rounded-circle" alt="">
                </div>
                <h3 class="text-center mb-2 fw-medium" id="deleteBookingCommentModalLabel">
                    {{ translate('Are_you_sure_you_want_to_delete_this_item') }}
                </h3>
                <p class="text-center small text-muted mb-0">{{ translate('This_action_cannot_be_undone') }}</p>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-danger" id="deleteBookingCommentConfirmBtn">
                        {{ translate('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        if (window.__bookingCommentActionsBound) {
            return;
        }
        window.__bookingCommentActionsBound = true;

        var pendingDeleteBtn = null;

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function deleteComment(deleteBtn) {
            var deleteUrl = deleteBtn.getAttribute('data-url');
            var confirmBtn = document.getElementById('deleteBookingCommentConfirmBtn');
            if (!deleteUrl || deleteBtn.disabled) {
                return;
            }
            deleteBtn.disabled = true;
            if (confirmBtn) {
                confirmBtn.disabled = true;
            }
            fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('delete failed');
                    }
                    window.location.reload();
                })
                .catch(function () {
                    deleteBtn.disabled = false;
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                    }
                    var modalEl = document.getElementById('deleteBookingCommentModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(@json(translate('Failed_to_update')));
                    }
                });
        }

        function bookingCommentButton(event, selector) {
            var btn = event.target.closest(selector);
            if (!btn || !btn.closest('#booking-activity, #booking-comments')) {
                return null;
            }
            return btn;
        }

        document.addEventListener('click', function (event) {
            var pinBtn = bookingCommentButton(event, '.lead-comment-pin-btn');
            if (pinBtn) {
                event.preventDefault();
                var pinUrl = pinBtn.getAttribute('data-url');
                if (!pinUrl || pinBtn.disabled) {
                    return;
                }
                pinBtn.disabled = true;
                fetch(pinUrl, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('pin failed');
                        }
                        window.location.reload();
                    })
                    .catch(function () {
                        pinBtn.disabled = false;
                        if (typeof toastr !== 'undefined') {
                            toastr.error(@json(translate('Failed_to_update')));
                        }
                    });
                return;
            }

            var deleteBtn = bookingCommentButton(event, '.lead-comment-delete-btn');
            if (!deleteBtn) {
                return;
            }

            event.preventDefault();
            var modalEl = document.getElementById('deleteBookingCommentModal');
            if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                return;
            }
            pendingDeleteBtn = deleteBtn;
            var confirmBtn = document.getElementById('deleteBookingCommentConfirmBtn');
            if (confirmBtn) {
                confirmBtn.disabled = false;
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('#deleteBookingCommentConfirmBtn')) {
                return;
            }
            if (!pendingDeleteBtn) {
                return;
            }
            deleteComment(pendingDeleteBtn);
        });
    })();
</script>
