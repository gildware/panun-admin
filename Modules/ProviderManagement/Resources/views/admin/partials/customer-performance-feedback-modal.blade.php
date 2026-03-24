<style>
    #customerPerformanceFeedbackModal {
        z-index: 20060 !important;
    }

    #customerPerformanceFeedbackModal .modal-dialog,
    #customerPerformanceFeedbackModal .modal-content,
    #customerPerformanceFeedbackModal .modal-body,
    #customerPerformanceFeedbackModal form,
    #customerPerformanceFeedbackModal label,
    #customerPerformanceFeedbackModal input,
    #customerPerformanceFeedbackModal textarea,
    #customerPerformanceFeedbackModal button {
        pointer-events: auto !important;
    }
</style>

@php
    $customerComplaintTags = \Modules\ProviderManagement\Entities\FeedbackTagConfig::query()
        ->where('entity_type', 'customer')
        ->where('feedback_type', 'complaint')
        ->where('is_active', 1)
        ->orderBy('tag_key')
        ->get();
    $customerPositiveTags = \Modules\ProviderManagement\Entities\FeedbackTagConfig::query()
        ->where('entity_type', 'customer')
        ->where('feedback_type', 'positive_feedback')
        ->where('is_active', 1)
        ->orderBy('tag_key')
        ->get();
    $customerNonComplaintTags = \Modules\ProviderManagement\Entities\FeedbackTagConfig::query()
        ->where('entity_type', 'customer')
        ->where('feedback_type', 'non_complaint')
        ->where('is_active', 1)
        ->orderBy('tag_key')
        ->get();
@endphp

<div class="modal fade" id="customerPerformanceFeedbackModal" tabindex="-1" aria-labelledby="customerPerformanceFeedbackModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="customerPerformanceFeedbackForm" method="POST" data-feedback-route="{{ route('admin.provider.customer-performance-feedback.store') }}">
                @csrf
                <div class="modal-header border-0 pb-1 align-items-start">
                    <div class="d-flex flex-column gap-1 flex-grow-1 pe-2">
                        <h5 class="mb-0" id="customerPerformanceFeedbackModalLabel">{{ translate('Customer Performance Feedback') }}</h5>
                        <div class="text-muted fs-12">{{ translate('Internal admin feedback for this customer on this booking.') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>

                <div class="modal-body p-4 pt-3">
                    <input type="hidden" name="context_booking_id" id="customerPerformanceContextBookingId">
                    <input type="hidden" name="customer_id" id="customerPerformanceCustomerId">
                    <input type="hidden" name="action_type" id="customerPerformanceActionType" value="completed">

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="mb-2">
                                <label class="form-label" id="customerPerformanceTypeLabel">{{ translate('Select Type') }}</label>
                                <div class="d-flex gap-4 flex-wrap">
                                    <div class="custom-radio">
                                        <input type="radio" id="cust_feedback_incident_complaint" name="incident_type" value="complaint" required>
                                        <label for="cust_feedback_incident_complaint">{{ translate('Complaint') }}</label>
                                    </div>
                                    <div class="custom-radio">
                                        <input type="radio" id="cust_feedback_incident_positive_feedback" name="incident_type" value="positive_feedback" required>
                                        <label for="cust_feedback_incident_positive_feedback">{{ translate('Positive Feedback') }}</label>
                                    </div>
                                    <div class="custom-radio">
                                        <input type="radio" id="cust_feedback_incident_non_complaint" name="incident_type" value="non_complaint" required>
                                        <label for="cust_feedback_incident_non_complaint">{{ translate('No Complaint (No Feedback)') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-2 d-none" id="customerComplaintTagsBlock">
                                <label class="form-label">{{ translate('Select Complaint Tags') }}</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    @foreach($customerComplaintTags as $tag)
                                        <div class="form-check">
                                            <input class="form-check-input customer-feedback-tag-checkbox" type="checkbox"
                                                   id="cust_tag_complaint_{{ $tag->tag_key }}" name="tags[]" value="{{ $tag->tag_key }}">
                                            <label class="form-check-label" for="cust_tag_complaint_{{ $tag->tag_key }}">{{ translate(ucwords(str_replace('_', ' ', $tag->tag_key))) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-2 d-none" id="customerPositiveFeedbackTagsBlock">
                                <label class="form-label">{{ translate('Select Positive Feedback Tags') }}</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    @foreach($customerPositiveTags as $tag)
                                        <div class="form-check">
                                            <input class="form-check-input customer-feedback-tag-checkbox" type="checkbox"
                                                   id="cust_tag_positive_{{ $tag->tag_key }}" name="tags[]" value="{{ $tag->tag_key }}">
                                            <label class="form-check-label" for="cust_tag_positive_{{ $tag->tag_key }}">{{ translate(ucwords(str_replace('_', ' ', $tag->tag_key))) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-2 d-none" id="customerNonComplaintTagsBlock">
                                <label class="form-label">{{ translate('Select Non-Complaint Tags') }}</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    @foreach($customerNonComplaintTags as $tag)
                                        <div class="form-check">
                                            <input class="form-check-input customer-feedback-tag-checkbox" type="checkbox"
                                                   id="cust_tag_non_{{ $tag->tag_key }}" name="tags[]" value="{{ $tag->tag_key }}">
                                            <label class="form-check-label" for="cust_tag_non_{{ $tag->tag_key }}">{{ translate(ucwords(str_replace('_', ' ', $tag->tag_key))) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-2">
                                <label for="customerPerformanceNotes" class="form-label">{{ translate('Notes (optional)') }}</label>
                                <textarea id="customerPerformanceNotes" name="notes" class="form-control" rows="3" maxlength="2000"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 pb-4 flex-column gap-2">
                    <button type="submit" class="btn btn--primary w-100" id="customerPerformanceFeedbackSubmit">
                        {{ translate('Submit_Feedback') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-100" id="customerPerformanceFeedbackSkip">
                        {{ translate('Skip_no_feedback') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function customerFeedbackToggleTagsByType() {
        const selectedType = document.querySelector('#customerPerformanceFeedbackForm input[name="incident_type"]:checked')?.value;
        const complaintBlock = document.getElementById('customerComplaintTagsBlock');
        const positiveBlock = document.getElementById('customerPositiveFeedbackTagsBlock');
        const nonComplaintBlock = document.getElementById('customerNonComplaintTagsBlock');

        if (!complaintBlock || !positiveBlock || !nonComplaintBlock) {
            return;
        }

        complaintBlock.classList.toggle('d-none', selectedType !== 'complaint');
        positiveBlock.classList.toggle('d-none', selectedType !== 'positive_feedback');
        nonComplaintBlock.classList.toggle('d-none', selectedType !== 'non_complaint');

        [complaintBlock, positiveBlock, nonComplaintBlock].forEach((block) => {
            if (block.classList.contains('d-none')) {
                block.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                    checkbox.checked = false;
                });
            }
        });
    }

    document.addEventListener('shown.bs.modal', function (event) {
        if (event?.target?.id !== 'customerPerformanceFeedbackModal') {
            return;
        }

        event.target.style.zIndex = '20060';
        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.style.zIndex = '20050';
        });

        const form = event.target.querySelector('#customerPerformanceFeedbackForm');
        if (form) {
            form.querySelectorAll('input, textarea, button').forEach(function (el) {
                if (el.type !== 'hidden' && el.id !== 'customerPerformanceFeedbackSkip') {
                    el.disabled = false;
                }
            });
            customerFeedbackToggleTagsByType();
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target?.form?.id === 'customerPerformanceFeedbackForm' && event.target?.name === 'incident_type') {
            customerFeedbackToggleTagsByType();
        }
    });

</script>
