@php
    $customerName = $customerName ?? booking_display_customer_name($booking, $booking->service_address ?? null);
    $customerPhone = $customerPhone ?? booking_display_customer_phone($booking, $booking->service_address ?? null);
@endphp
<div class="modal fade" id="addCallLogModal" tabindex="-1" aria-labelledby="addCallLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="addCallLogModalLabel">{{ translate('Add_Call_Log') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form method="POST"
                  action="{{ route('admin.booking.call-logs.store', $booking->id) }}"
                  enctype="multipart/form-data"
                  id="add-call-log-form"
                  data-store-url="{{ route('admin.booking.call-logs.store', $booking->id) }}"
                  data-update-url-template="{{ route('admin.booking.call-logs.update', [$booking->id, '__FOLLOWUP__']) }}">
                @csrf
                <input type="hidden" name="call_log_form" value="1">
                <input type="hidden" name="call_log_mode" id="call-log-mode-input" value="{{ old('call_log_mode', 'add') }}">
                <input type="hidden" name="call_log_followup_id" id="call-log-followup-id-input" value="{{ old('call_log_followup_id') }}">
                <input type="hidden" name="_method" id="call-log-method-input" value="PUT" disabled>
                <div class="modal-body pt-0">
                    @php
                        $defaultCalledPartyType = old(
                            'called_party_type',
                            \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_CUSTOMER
                        );
                    @endphp
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Who_You_Called') }}</label>
                        <div class="d-flex flex-wrap gap-3 mb-2">
                            @foreach([
                                \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_CUSTOMER => translate('Customer'),
                                \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_PROVIDER => translate('Provider'),
                                \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_OTHER => translate('Other'),
                            ] as $partyType => $partyLabel)
                                <div class="form-check">
                                    <input class="form-check-input js-call-log-party-type"
                                           type="radio"
                                           name="called_party_type"
                                           id="call-log-party-{{ $partyType }}"
                                           value="{{ $partyType }}"
                                           {{ $defaultCalledPartyType === $partyType ? 'checked' : '' }}>
                                    <label class="form-check-label" for="call-log-party-{{ $partyType }}">{{ $partyLabel }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('called_party_type')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror

                        <div class="call-log-party-panel call-log-party-panel--customer {{ $defaultCalledPartyType === \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_CUSTOMER ? '' : 'd-none' }}">
                            <label class="form-label small text-muted mb-1">{{ translate('Customer') }}</label>
                            <input type="text"
                                   class="form-control mb-2"
                                   value="{{ $customerName ?: '—' }}"
                                   readonly>
                            <input type="text"
                                   class="form-control"
                                   value="{{ $customerPhone ?: '—' }}"
                                   readonly>
                        </div>

                        <div class="call-log-party-panel call-log-party-panel--provider {{ $defaultCalledPartyType === \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_PROVIDER ? '' : 'd-none' }}">
                            <label class="form-label small text-muted mb-1" for="call-log-provider-select">{{ translate('Select_Provider') }}</label>
                            <select name="called_provider_id"
                                    id="call-log-provider-select"
                                    class="form-control"
                                    data-placeholder="{{ translate('Search_provider_by_name_or_phone') }}"
                                    data-selected="{{ old('called_provider_id') }}">
                                <option value="">{{ translate('Select_Provider') }}</option>
                            </select>
                            @error('called_provider_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div id="call-log-provider-preview" class="small text-muted mt-2 d-none"></div>
                        </div>

                        <div class="call-log-party-panel call-log-party-panel--other {{ $defaultCalledPartyType === \Modules\BookingModule\Entities\BookingFollowup::CALLED_PARTY_OTHER ? '' : 'd-none' }}">
                            <label class="form-label small text-muted mb-1" for="call-log-other-name">{{ translate('Name') }}</label>
                            <input type="text"
                                   name="called_name"
                                   id="call-log-other-name"
                                   class="form-control mb-2"
                                   value="{{ old('called_name') }}"
                                   maxlength="255">
                            @error('called_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <label class="form-label small text-muted mb-1" for="call-log-other-number">{{ translate('Phone') }}</label>
                            <input type="text"
                                   name="called_number"
                                   id="call-log-other-number"
                                   class="form-control"
                                   value="{{ old('called_number') }}"
                                   maxlength="32">
                            @error('called_number')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('When_You_Called') }}</label>
                        <input type="datetime-local"
                               name="called_at"
                               id="call-log-called-at-input"
                               class="form-control"
                               value="{{ old('called_at', now()->format('Y-m-d\TH:i')) }}"
                               required>
                        @error('called_at')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Remarks') }}</label>
                        <textarea name="remarks"
                                  class="form-control"
                                  rows="3"
                                  placeholder="{{ translate('Add_call_log_remarks') }}">{{ old('remarks') }}</textarea>
                        @error('remarks')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ translate('Voice_Recording') }}</label>
                        <div id="call-log-current-recording" class="small text-muted mb-2 d-none"></div>
                        <input type="file"
                               name="recording"
                               id="call-log-recording-input"
                               class="form-control"
                               accept="audio/*,.mp3,.wav,.webm,.ogg,.m4a,.aac">
                        <div class="form-text">{{ translate('Upload_call_recording_optional_max_10MB') }}</div>
                        @error('recording')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2 pb-4">
                    <button type="button"
                            class="btn btn--secondary"
                            data-bs-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn--primary" id="call-log-submit-btn">
                        {{ translate('Add_Call_Log') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
