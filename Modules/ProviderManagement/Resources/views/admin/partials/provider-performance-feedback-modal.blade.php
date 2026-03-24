<style>
    #providerPerformanceFeedbackModal {
        z-index: 20060 !important;
    }

    #providerPerformanceFeedbackModal .modal-dialog,
    #providerPerformanceFeedbackModal .modal-content,
    #providerPerformanceFeedbackModal .modal-body,
    #providerPerformanceFeedbackModal form,
    #providerPerformanceFeedbackModal label,
    #providerPerformanceFeedbackModal input,
    #providerPerformanceFeedbackModal textarea,
    #providerPerformanceFeedbackModal button {
        pointer-events: auto !important;
    }
</style>

@php
    $providerComplaintTags = \Modules\ProviderManagement\Entities\FeedbackTagConfig::query()
        ->where('entity_type', 'provider')
        ->where('feedback_type', 'complaint')
        ->where('is_active', 1)
        ->orderBy('tag_key')
        ->get();
    $providerPositiveTags = \Modules\ProviderManagement\Entities\FeedbackTagConfig::query()
        ->where('entity_type', 'provider')
        ->where('feedback_type', 'positive_feedback')
        ->where('is_active', 1)
        ->orderBy('tag_key')
        ->get();
    $providerNonComplaintTags = \Modules\ProviderManagement\Entities\FeedbackTagConfig::query()
        ->where('entity_type', 'provider')
        ->where('feedback_type', 'non_complaint')
        ->where('is_active', 1)
        ->orderBy('tag_key')
        ->get();
@endphp

<div class="modal fade" id="providerPerformanceFeedbackModal" tabindex="-1" aria-labelledby="providerPerformanceFeedbackModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="providerPerformanceFeedbackForm" method="POST" data-feedback-route="{{ route('admin.provider.provider-performance-feedback.store') }}">
                @csrf
                <div class="modal-header border-0 pb-1 align-items-start">
                    <div class="d-flex flex-column gap-1 flex-grow-1 pe-2">
                        <h5 class="mb-0" id="providerPerformanceFeedbackModalLabel">{{ translate('Provider Performance Feedback') }}</h5>
                        <div class="text-muted fs-12">{{ translate('This form is mandatory for internal tracking.') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>

                <div class="modal-body p-4 pt-3">
                    <input type="hidden" name="context_booking_id" id="providerPerformanceContextBookingId">
                    <input type="hidden" name="provider_id" id="providerPerformanceProviderId">
                    <input type="hidden" name="action_type" id="providerPerformanceActionType" value="completed">

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="mb-2">
                                <label class="form-label" id="providerPerformanceTypeLabel">{{ translate('Select Type') }}</label>
                                <div class="d-flex gap-4 flex-wrap">
                                    <div class="custom-radio">
                                        <input type="radio" id="feedback_incident_complaint" name="incident_type" value="complaint" required>
                                        <label for="feedback_incident_complaint">{{ translate('Complaint') }}</label>
                                    </div>
                                    <div class="custom-radio">
                                        <input type="radio" id="feedback_incident_positive_feedback" name="incident_type" value="positive_feedback" required>
                                        <label for="feedback_incident_positive_feedback">{{ translate('Positive Feedback') }}</label>
                                    </div>
                                    <div class="custom-radio">
                                        <input type="radio" id="feedback_incident_non_complaint" name="incident_type" value="non_complaint" required>
                                        <label for="feedback_incident_non_complaint">{{ translate('No Complaint (No Feedback)') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-2 d-none" id="complaintTagsBlock">
                                <label class="form-label">{{ translate('Select Complaint Tags') }}</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    @foreach($providerComplaintTags as $tag)
                                        <div class="form-check">
                                            <input class="form-check-input provider-feedback-tag-checkbox" type="checkbox"
                                                   id="tag_{{ $tag->tag_key }}" name="tags[]" value="{{ $tag->tag_key }}">
                                            <label class="form-check-label" for="tag_{{ $tag->tag_key }}">{{ translate(ucwords(str_replace('_', ' ', $tag->tag_key))) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-2 d-none" id="positiveFeedbackTagsBlock">
                                <label class="form-label">{{ translate('Select Positive Feedback Tags') }}</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    @foreach($providerPositiveTags as $tag)
                                        <div class="form-check">
                                            <input class="form-check-input provider-feedback-tag-checkbox" type="checkbox"
                                                   id="tag_{{ $tag->tag_key }}" name="tags[]" value="{{ $tag->tag_key }}">
                                            <label class="form-check-label" for="tag_{{ $tag->tag_key }}">{{ translate(ucwords(str_replace('_', ' ', $tag->tag_key))) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-2 d-none" id="nonComplaintTagsBlock">
                                <label class="form-label">{{ translate('Select Non-Complaint Tags') }}</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    @foreach($providerNonComplaintTags as $tag)
                                        <div class="form-check">
                                            <input class="form-check-input provider-feedback-tag-checkbox" type="checkbox"
                                                   id="tag_{{ $tag->tag_key }}" name="tags[]" value="{{ $tag->tag_key }}">
                                            <label class="form-check-label" for="tag_{{ $tag->tag_key }}">{{ translate(ucwords(str_replace('_', ' ', $tag->tag_key))) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-2">
                                <label for="providerPerformanceNotes" class="form-label">{{ translate('Notes (optional)') }}</label>
                                <textarea id="providerPerformanceNotes" name="notes" class="form-control" rows="3" maxlength="2000"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 pb-4">
                    <button type="submit" class="btn btn--primary w-100" id="providerPerformanceFeedbackSubmit">
                        {{ translate('Submit_Feedback') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function providerFeedbackToggleTagsByType() {
        const selectedType = document.querySelector('#providerPerformanceFeedbackForm input[name="incident_type"]:checked')?.value;
        const complaintBlock = document.getElementById('complaintTagsBlock');
        const positiveBlock = document.getElementById('positiveFeedbackTagsBlock');
        const nonComplaintBlock = document.getElementById('nonComplaintTagsBlock');

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

    function providerFeedbackUpdateTypePrompt() {
        const actionType = document.getElementById('providerPerformanceActionType')?.value;
        const labelEl = document.getElementById('providerPerformanceTypeLabel');
        if (!labelEl) {
            return;
        }

        if (actionType === 'provider_changed') {
            labelEl.innerText = "{{ translate('Why provider was changed?') }}";
        } else {
            labelEl.innerText = "{{ translate('Select Type') }}";
        }
    }

    document.addEventListener('shown.bs.modal', function (event) {
        if (event?.target?.id !== 'providerPerformanceFeedbackModal') {
            return;
        }

        // Keep feedback modal above any previously opened modal/backdrop.
        event.target.style.zIndex = '20060';
        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.style.zIndex = '20050';
        });

        // Ensure fields are interactive even if any previous flow disabled form controls.
        const form = event.target.querySelector('#providerPerformanceFeedbackForm');
        if (form) {
            form.querySelectorAll('input, textarea, button').forEach(function (el) {
                if (el.type !== 'hidden') {
                    el.disabled = false;
                }
            });
            providerFeedbackUpdateTypePrompt();
            providerFeedbackToggleTagsByType();
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target?.name === 'incident_type') {
            providerFeedbackToggleTagsByType();
        }
    });

    $(document).on('submit', '#providerPerformanceFeedbackForm', function (e) {
        const $form = $(this);
        const type = $form.find('input[name="incident_type"]:checked').val();
        if (!type) {
            return;
        }

        const selectedTagsCount = $form.find('.provider-feedback-tag-checkbox:checked').length;
        if (selectedTagsCount < 1) {
            e.preventDefault();
            toastr.error('{{ translate('Please select at least one tag.') }}');
        }
    });
</script>

