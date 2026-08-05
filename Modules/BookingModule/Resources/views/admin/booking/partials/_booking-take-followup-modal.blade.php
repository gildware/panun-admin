@php
    $requiresMandatoryNextFollowup = $requiresMandatoryNextFollowup ?? $booking->requiresMandatoryNextFollowup();
    $followupScheduleMinAt = $followupScheduleMinAt ?? now()->format('Y-m-d\TH:i');
    $redirectWebPage = $redirectWebPage ?? request('web_page', 'details');
    $defaultNextAt = \Carbon\Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d\TH:i');
@endphp
<div class="modal fade" id="takeFollowupModal" tabindex="-1" aria-labelledby="takeFollowupModalLabel" aria-hidden="true" data-mandatory-next="{{ $requiresMandatoryNextFollowup ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="takeFollowupModalLabel">{{ translate('Take_Follow_up') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST"
                  action=""
                  enctype="multipart/form-data"
                  id="booking-take-followup-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_web_page" value="{{ $redirectWebPage }}">
                <input type="hidden" name="followup_mode" id="booking-followup-mode-input" value="take">
                <div class="modal-body pt-0">
                    <p class="text-muted small mb-3" id="booking-take-followup-context"></p>

                    <div class="mb-3" id="booking-followup-status-group">
                        <label class="form-label">{{ translate('Status') }}</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="radio"
                                       name="followup_action"
                                       id="booking-followup-action-taken"
                                       value="{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_TAKEN }}"
                                       checked>
                                <label class="form-check-label" for="booking-followup-action-taken">{{ translate('Taken') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="radio"
                                       name="followup_action"
                                       id="booking-followup-action-reschedule"
                                       value="{{ \Modules\BookingModule\Entities\BookingFollowup::ACTION_RESCHEDULE }}">
                                <label class="form-check-label" for="booking-followup-action-reschedule">{{ translate('Reschedule') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="followup-modal-section mb-3" id="booking-followup-current-section">
                        <h6 class="followup-modal-section-title" id="booking-followup-current-section-title">{{ translate('This_Follow_up') }}</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6" id="booking-followup-datetime-group">
                                <label class="form-label">{{ translate('Date_Time') }}</label>
                                <input type="datetime-local"
                                       name="followup_at"
                                       id="booking-followup-at-input"
                                       class="form-control"
                                       value="{{ now()->format('Y-m-d\TH:i') }}"
                                       required>
                            </div>
                            <div class="col-sm-6" id="booking-followup-channel-group">
                                <label class="form-label">{{ translate('Follow_up_Taken_on') }}</label>
                                <select name="contact_channel"
                                        id="booking-followup-contact-channel"
                                        class="form-control">
                                    <option value="{{ \Modules\BookingModule\Entities\BookingFollowup::CHANNEL_CALL }}">{{ translate('Call') }}</option>
                                    <option value="{{ \Modules\BookingModule\Entities\BookingFollowup::CHANNEL_WHATSAPP }}">{{ translate('WhatsApp') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 d-none" id="booking-followup-recording-group">
                            <label class="form-label">{{ translate('Voice_Recording') }}</label>
                            <input type="file"
                                   name="recording"
                                   id="booking-followup-recording-input"
                                   class="form-control"
                                   accept="audio/*,.mp3,.wav,.webm,.ogg,.m4a,.aac">
                            <div class="form-text">{{ translate('Upload_call_recording_optional_max_10MB') }}</div>
                        </div>
                        <div class="mb-0" id="booking-followup-remarks-group">
                            <label class="form-label" id="booking-followup-remarks-label">
                                {{ translate('Remarks') }} <span class="text-danger">*</span>
                            </label>
                            <textarea name="remarks"
                                      id="booking-followup-remarks-input"
                                      class="form-control"
                                      rows="3"
                                      placeholder="{{ translate('Add_remarks_from_follow_up') }}"
                                      required></textarea>
                            @error('remarks')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="followup-modal-section mb-0" id="booking-followup-next-section">
                        <h6 class="followup-modal-section-title">{{ translate('Next_Follow_up') }}</h6>
                        <p class="followup-modal-section-help">{{ translate('Schedule_and_priority_for_the_upcoming_follow_up') }}</p>
                        @if(!$requiresMandatoryNextFollowup)
                            <div class="form-check mb-3" id="booking-schedule-next-wrap">
                                <input type="checkbox" class="form-check-input" name="schedule_next" value="1" id="booking-schedule-next-checkbox">
                                <label class="form-check-label" for="booking-schedule-next-checkbox">{{ translate('Schedule_another_follow_up') }}</label>
                            </div>
                        @endif
                        <div class="mb-3" id="booking-followup-urgency-group">
                            <label class="form-label">{{ translate('Urgency') }}</label>
                            <select name="urgency" class="form-control" id="booking-followup-urgency-select">
                                <option value="high">{{ translate('High') }}</option>
                                <option value="medium" selected>{{ translate('Medium') }}</option>
                                <option value="low">{{ translate('Low') }}</option>
                            </select>
                        </div>
                        <div class="mb-0" id="booking-next-followup-group">
                            <label class="form-label" id="booking-next-followup-label">
                                {{ translate('Next_Follow_up_Date') }} @if($requiresMandatoryNextFollowup)<span class="text-danger">*</span>@endif
                            </label>
                            <input type="datetime-local"
                                   name="next_followup_at"
                                   id="booking-next-followup-input"
                                   class="form-control js-followup-future-only"
                                   min="{{ $followupScheduleMinAt }}"
                                   data-default="{{ $defaultNextAt }}"
                                   value="{{ $defaultNextAt }}"
                                   @if($requiresMandatoryNextFollowup) required @endif>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary" id="booking-followup-submit-btn">{{ translate('Save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
