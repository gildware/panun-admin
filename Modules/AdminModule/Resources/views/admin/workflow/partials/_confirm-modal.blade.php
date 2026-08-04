@once
    @push('css_or_js')
        <style>
            #workflowConfirmModal.show {
                z-index: 20060;
            }
            body:has(#workflowConfirmModal.show) .modal-backdrop.show {
                z-index: 20055;
            }
            .swal2-container {
                z-index: 20100 !important;
            }
        </style>
    @endpush
@endonce
<div class="modal fade" id="workflowConfirmModal" tabindex="-1" aria-labelledby="workflowConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="workflowConfirmModalLabel">
                    <span class="material-icons align-middle me-1">help_outline</span>
                    {{ translate('Confirm_previous_steps') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="workflow-confirm-intro">
                    {{ translate('Please_confirm_you_completed_these_steps_before_continuing') }}
                </p>
                <p class="small text-muted mb-3 d-none" id="workflow-confirm-intro-post">
                    {{ translate('You_updated_the_panel_confirm_you_also_completed') }}
                </p>
                <p class="small mb-3 d-none" id="workflow-confirm-intro-hard">
                    {{ translate('Complete_the_steps_below_then_try_again_Use_Next_Step_button_for_checkboxes') }}
                </p>
                <ul class="list-unstyled mb-0" id="workflow-confirm-steps"></ul>
                <div class="alert alert-warning small d-none mt-3 mb-0" id="workflow-confirm-hard-notice"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Not_yet') }}</button>
                <button type="button" class="btn btn--primary" id="workflow-confirm-proceed" disabled>
                    {{ translate('Yes_continue') }}
                </button>
            </div>
        </div>
    </div>
</div>
