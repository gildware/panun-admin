<div class="modal fade" id="deleteLeadFollowupModal" tabindex="-1" aria-labelledby="deleteLeadFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body pt-5 p-md-5">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>

                <div class="d-flex justify-content-center mb-4">
                    <img width="75" height="75"
                         src="{{ asset('assets/admin-module/img/media/delete.png') }}"
                         class="rounded-circle" alt="">
                </div>

                <h3 class="text-center mb-2 fw-medium" id="deleteLeadFollowupModalLabel">
                    {{ translate('Are_you_sure_you_want_to_delete_this_item') }}
                </h3>
                <p class="text-center fs-12 fw-medium text-muted mb-1" id="deleteLeadFollowupModalItem"></p>
                <p class="text-center small text-muted mb-0">{{ translate('This_action_cannot_be_undone') }}</p>

                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-danger" id="deleteLeadFollowupConfirmBtn">
                        {{ translate('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
