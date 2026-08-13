<div class="modal fade" id="editLeadFollowupModal" tabindex="-1" aria-labelledby="editLeadFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="editLeadFollowupModalLabel">{{ translate('Edit_Follow_up') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST"
                  action=""
                  id="lead-edit-followup-form"
                  data-turbo="false"
                  novalidate>
                @csrf
                @method('PUT')
                @if(!empty($inModal))
                    <input type="hidden" name="in_modal" value="1">
                @endif
                <input type="hidden" name="edit_followup_id" id="lead-edit-followup-id-input" value="">
                <div class="modal-body pt-0">
                    <p class="text-muted small mb-3" id="lead-edit-followup-status-label"></p>

                    <div class="mb-3">
                        <label class="form-label">{{ translate('Scheduled_for') }} <span class="text-danger">*</span></label>
                        <input type="datetime-local"
                               name="date"
                               id="lead-edit-followup-date"
                               class="form-control"
                               required>
                    </div>

                    <div class="mb-3 d-none" id="lead-edit-followup-at-group">
                        <label class="form-label">{{ translate('Taken_on') }}</label>
                        <input type="datetime-local"
                               name="followup_at"
                               id="lead-edit-followup-at"
                               class="form-control js-followup-not-future"
                               max="{{ now()->format('Y-m-d\TH:i') }}">
                        @error('followup_at')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 d-none" id="lead-edit-followup-channel-group">
                        <label class="form-label">{{ translate('Follow_up_Taken_on') }}</label>
                        <select name="contact_channel" id="lead-edit-followup-channel" class="form-control">
                            <option value="">{{ translate('None') }}</option>
                            <option value="{{ \Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_CALL }}">{{ translate('Call') }}</option>
                            <option value="{{ \Modules\LeadManagement\Entities\LeadFollowup::CHANNEL_WHATSAPP }}">{{ translate('WhatsApp') }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ translate('Urgency') }}</label>
                        <select name="urgency" id="lead-edit-followup-urgency" class="form-control">
                            <option value="high">{{ translate('High') }}</option>
                            <option value="medium">{{ translate('Medium') }}</option>
                            <option value="low">{{ translate('Low') }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ translate('Remarks') }}</label>
                        <textarea name="remarks"
                                  id="lead-edit-followup-remarks"
                                  class="form-control"
                                  rows="3"
                                  placeholder="{{ translate('Add_remarks_from_follow_up') }}"></textarea>
                    </div>

                    <div class="mb-0" id="lead-edit-followup-next-group">
                        <label class="form-label">{{ translate('Next_Follow_up_Date') }}</label>
                        <input type="datetime-local"
                               name="next_followup_at"
                               id="lead-edit-followup-next"
                               class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
