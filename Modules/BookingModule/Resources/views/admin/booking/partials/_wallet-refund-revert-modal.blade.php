@can('booking_can_manage_status')
    <div class="modal fade" id="revertWalletRefundConfirm-{{ $booking->id }}" tabindex="-1" aria-labelledby="revertWalletRefundConfirmLabel-{{ $booking->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title d-flex align-items-center gap-2 text-danger fz-16 mb-0" id="revertWalletRefundConfirmLabel-{{ $booking->id }}">
                        <span class="material-symbols-outlined" aria-hidden="true">undo</span>
                        {{ translate('Revert_wallet_refund') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="alert alert-warning mb-3 fz-12" role="alert">
                        {{ translate('Revert_wallet_refund_confirm') }}
                    </div>
                    <p class="text-muted fz-12 mb-0 fw-medium" id="revertWalletRefundSummaryLine-{{ $booking->id }}"></p>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <form method="post" action="{{ route('admin.booking.refund_to_wallet.revert', $booking->id) }}" class="d-inline wallet-refund-revert-form" id="revertWalletRefundForm-{{ $booking->id }}">
                        @csrf
                        <input type="hidden" name="ledger_id" id="revertWalletRefundLedgerId-{{ $booking->id }}" value="">
                        <button type="submit" class="btn btn-danger">{{ translate('Revert_wallet_refund') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var confirmEl = document.getElementById('revertWalletRefundConfirm-{{ $booking->id }}');
            if (!confirmEl) return;
            confirmEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                var idField = document.getElementById('revertWalletRefundLedgerId-{{ $booking->id }}');
                var summary = document.getElementById('revertWalletRefundSummaryLine-{{ $booking->id }}');
                if (!trigger || !idField) return;
                idField.value = trigger.getAttribute('data-ledger-id') || '';
                if (summary) {
                    summary.textContent = trigger.getAttribute('data-amount-line') || '';
                }
            });
            var form = document.getElementById('revertWalletRefundForm-{{ $booking->id }}');
            if (form) {
                form.addEventListener('submit', function () {
                    var btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        if (btn.disabled) {
                            return false;
                        }
                        btn.disabled = true;
                    }
                    return true;
                });
            }
        })();
    </script>
@endcan
